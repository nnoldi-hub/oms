<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\Ingredient;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Importa retete dintr-un fisier CSV sau XLSX si actualizeaza meniurile,
 * ingredientele din retete si preturile din catalogul global.
 *
 * Fisierul trebuie sa aiba coloanele: Reteta, Tip, Ingredient,
 * Cantitate / persoana, Unitate, Pret unitar (RON). Coloanele
 * "Cantitate pt. portii de referinta" si "Cost / portie (RON)" sunt ignorate.
 */
class RecipeImportService
{
    private const TYPE_ALIASES = [
        'fel principal' => 'main',
        'principal' => 'main',
        'main' => 'main',
        'ciorba' => 'soup',
        'supa' => 'soup',
        'soup' => 'soup',
        'desert' => 'dessert',
        'dessert' => 'dessert',
    ];

    /**
     * Denumiri din foaia de lucru care trebuie sa actualizeze o reteta existenta,
     * nu sa creeze una noua.
     */
    private const RECIPE_NAME_ALIASES = [
        'ciorba de fasole exemplu a 5 a ciorba' => 'Ciorba de fasole',
    ];

    /**
     * Citeste randurile fisierului si le intoarce cu chei normalizate.
     *
     * @return list<array<string, string>>
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("Fisierul nu exista: {$path}");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException("Fisierul nu poate fi citit: {$path}");
        }

        $magic = (string) fread($handle, 4);
        fclose($handle);

        if ($magic === "PK\x03\x04") {
            return $this->readXlsx($path);
        }

        return $this->readDelimited($path);
    }

    /**
     * Valideaza randurile si grupeaza ingredientele pe retete.
     *
     * @param  list<array<string, string>>  $rows
     * @return array{
     *     recipes: list<array{name: string, type: string, ingredients: list<array{name: string, unit: string, quantity_per_person: float, unit_price: float|null}>}>,
     *     prices: array<string, float>,
     *     unpriced: list<array{name: string, unit: string}>,
     *     errors: list<string>
     * }
     */
    public function plan(array $rows): array
    {
        $recipes = [];
        $prices = [];
        $unpriced = [];
        $errors = [];
        $lineNumber = 1;

        foreach ($rows as $row) {
            $lineNumber++;

            $recipeName = $this->canonicalRecipeName(trim((string) ($row['recipe'] ?? '')));
            $recipeType = $this->parseType((string) ($row['type'] ?? ''));
            $ingredientName = trim((string) ($row['ingredient'] ?? ''));
            $unit = $this->parseUnit((string) ($row['unit'] ?? ''));
            $quantity = $this->parseNumber($row['quantity'] ?? null);
            $price = $this->parseNumber($row['price'] ?? null);

            if ($recipeName === '') {
                $errors[] = "Linia {$lineNumber}: denumirea retetei lipseste.";

                continue;
            }

            if ($recipeType === null) {
                $errors[] = "Linia {$lineNumber}: tipul retetei '{$row['type']}' nu este recunoscut.";

                continue;
            }

            if ($ingredientName === '') {
                $errors[] = "Linia {$lineNumber}: denumirea ingredientului lipseste.";

                continue;
            }

            if (! in_array($unit, Ingredient::UNITS, true)) {
                $errors[] = "Linia {$lineNumber}: unitatea '{$row['unit']}' nu este valida (kg, g, l, buc).";

                continue;
            }

            if ($this->rawNumber($row['quantity'] ?? null) !== '' && $quantity === null) {
                $errors[] = "Linia {$lineNumber}: cantitatea nu este un numar valid.";

                continue;
            }

            if ($quantity === null || $quantity <= 0) {
                $errors[] = "Linia {$lineNumber}: cantitatea per persoana trebuie sa fie pozitiva.";

                continue;
            }

            if ($this->rawNumber($row['price'] ?? null) !== '' && $price === null) {
                $errors[] = "Linia {$lineNumber}: pretul unitar nu este un numar valid.";

                continue;
            }

            if ($price !== null && $price < 0) {
                $errors[] = "Linia {$lineNumber}: pretul unitar nu poate fi negativ.";

                continue;
            }

            $recipeKey = mb_strtolower($recipeName);

            if (! isset($recipes[$recipeKey])) {
                $recipes[$recipeKey] = [
                    'name' => $recipeName,
                    'type' => $recipeType,
                    'ingredients' => [],
                ];
            } elseif ($recipes[$recipeKey]['type'] !== $recipeType) {
                $errors[] = "Linia {$lineNumber}: reteta '{$recipeName}' are tipuri diferite in fisier.";

                continue;
            }

            $ingredientKey = $this->ingredientKey($ingredientName, $unit);

            if ($price !== null) {
                if (isset($prices[$ingredientKey]) && abs($prices[$ingredientKey] - $price) > 0.0001) {
                    $errors[] = sprintf(
                        'Linia %d: preturi diferite pentru %s (%s): %.2f si %.2f.',
                        $lineNumber,
                        $ingredientName,
                        $unit,
                        $prices[$ingredientKey],
                        $price,
                    );

                    continue;
                }

                $prices[$ingredientKey] = $price;
            }

            $recipes[$recipeKey]['ingredients'][] = [
                'name' => $ingredientName,
                'unit' => $unit,
                'quantity_per_person' => round($quantity, 4),
                'unit_price' => $price,
            ];
        }

        foreach ($recipes as $recipe) {
            foreach ($recipe['ingredients'] as $ingredient) {
                $key = $this->ingredientKey($ingredient['name'], $ingredient['unit']);

                if (isset($prices[$key])) {
                    continue;
                }

                $unpriced[$key] = [
                    'name' => $ingredient['name'],
                    'unit' => $ingredient['unit'],
                ];
            }
        }

        return [
            'recipes' => array_values($recipes),
            'prices' => $prices,
            'unpriced' => array_values($unpriced),
            'errors' => $errors,
        ];
    }

    /**
     * Aplica planul: creeaza/actualizeaza ingredientele si retetele.
     *
     * @param  array<string, mixed>  $plan
     * @return array{recipes_created: int, recipes_updated: int, ingredients_created: int, prices_updated: int, new_menu_ids: list<int>}
     */
    public function apply(array $plan): array
    {
        $stats = [
            'recipes_created' => 0,
            'recipes_updated' => 0,
            'ingredients_created' => 0,
            'prices_updated' => 0,
            'new_menu_ids' => [],
        ];

        DB::transaction(function () use ($plan, &$stats): void {
            $ingredients = [];

            foreach ($plan['recipes'] as $recipe) {
                foreach ($recipe['ingredients'] as $line) {
                    $key = $this->ingredientKey($line['name'], $line['unit']);

                    if (isset($ingredients[$key])) {
                        continue;
                    }

                    $price = $plan['prices'][$key] ?? null;
                    $ingredient = Ingredient::firstOrCreate(
                        ['name' => $line['name'], 'unit' => $line['unit']],
                        ['unit_price' => $price, 'is_active' => true],
                    );

                    if ($ingredient->wasRecentlyCreated) {
                        $stats['ingredients_created']++;
                    } elseif ($price !== null && (float) $ingredient->unit_price !== (float) $price) {
                        $ingredient->update(['unit_price' => $price]);
                        $stats['prices_updated']++;
                    }

                    $ingredients[$key] = $ingredient;
                }
            }

            foreach ($plan['recipes'] as $recipe) {
                $menu = Menu::firstOrNew(['name' => $recipe['name']]);
                $isNew = ! $menu->exists;

                $lines = [];

                foreach ($recipe['ingredients'] as $line) {
                    $ingredient = $ingredients[$this->ingredientKey($line['name'], $line['unit'])];

                    $lines[] = [
                        'ingredient_id' => $ingredient->id,
                        'name' => $ingredient->name,
                        'unit' => $ingredient->unit,
                        'quantity_per_person' => $line['quantity_per_person'],
                    ];
                }

                $menu->type = $recipe['type'];
                $menu->ingredients = $lines;

                if ($isNew) {
                    $menu->instructions = null;
                    $menu->allergens = [];
                    $menu->packaging_cost = 0;
                    $menu->is_active = true;
                }

                $menu->save();

                if ($isNew) {
                    $stats['recipes_created']++;
                    $stats['new_menu_ids'][] = $menu->id;
                } else {
                    $stats['recipes_updated']++;
                }
            }

            if ($stats['new_menu_ids'] !== []) {
                Congregation::query()->each(
                    fn (Congregation $congregation) => $congregation->menus()->syncWithoutDetaching($stats['new_menu_ids'])
                );
            }
        });

        return $stats;
    }

    /**
     * @return list<array<string, string>>
     */
    private function readDelimited(string $path): array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new InvalidArgumentException("Fisierul nu poate fi citit: {$path}");
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        $lines = array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));

        if ($lines === []) {
            throw new InvalidArgumentException('Fisierul este gol.');
        }

        $delimiter = $this->detectDelimiter($lines[0]);
        $headers = str_getcsv($lines[0], $delimiter, '"', '\\');
        $columns = $this->columnMap($headers);
        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            $values = str_getcsv($line, $delimiter, '"', '\\');
            $row = $this->mapRow($values, $columns);

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return list<array<string, string>>
     */
    private function readXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new InvalidArgumentException('Extensia PHP zip este necesara pentru fisierele XLSX.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException("Fisierul XLSX nu poate fi deschis: {$path}");
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheetPath = $this->sheetPath($zip, 'Retete');
            $content = $zip->getFromName($sheetPath);

            if ($content === false) {
                throw new InvalidArgumentException('Foaia de retete nu a fost gasita in fisierul XLSX.');
            }

            $xml = @simplexml_load_string($content);

            if ($xml === false) {
                throw new InvalidArgumentException('Fisierul XLSX nu poate fi interpretat.');
            }

            $columns = null;
            $rows = [];

            foreach ($xml->sheetData->row as $row) {
                $values = [];
                $lastColumn = 0;

                foreach ($row->c as $cell) {
                    $index = $this->columnIndex((string) $cell['r']);
                    $values[$index] = $this->cellValue($cell, $sharedStrings);
                    $lastColumn = max($lastColumn, $index);
                }

                $line = [];

                for ($index = 0; $index <= $lastColumn; $index++) {
                    $line[$index] = $values[$index] ?? '';
                }

                if ($columns === null) {
                    $columns = $this->columnMap($line);

                    continue;
                }

                $mapped = $this->mapRow($line, $columns);

                if ($this->rowIsEmpty($mapped)) {
                    continue;
                }

                $rows[] = $mapped;
            }

            if ($columns === null) {
                throw new InvalidArgumentException('Foaia de retete este goala.');
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<string, int>
     */
    private function columnMap(array $headers): array
    {
        $columns = [];

        foreach ($headers as $index => $header) {
            $key = $this->normalize((string) $header);

            if ($key === '') {
                continue;
            }

            if (str_contains($key, 'reteta')) {
                $columns['recipe'] = $index;
            } elseif (str_contains($key, 'tip')) {
                $columns['type'] = $index;
            } elseif (str_contains($key, 'ingredient')) {
                $columns['ingredient'] = $index;
            } elseif (str_contains($key, 'unitate')) {
                $columns['unit'] = $index;
            } elseif (str_contains($key, 'referinta')) {
                $columns['reference_quantity'] = $index;
            } elseif (str_contains($key, 'pret')) {
                $columns['price'] = $index;
            } elseif (str_contains($key, 'cost')) {
                $columns['cost'] = $index;
            } elseif (str_contains($key, 'cantitate') && str_contains($key, 'persoana')) {
                $columns['quantity'] = $index;
            }
        }

        foreach (['recipe', 'type', 'ingredient', 'quantity', 'unit'] as $required) {
            if (! array_key_exists($required, $columns)) {
                throw new InvalidArgumentException(
                    'Fisierul trebuie sa contina coloanele: Reteta, Tip, Ingredient, Cantitate / persoana, Unitate.'
                );
            }
        }

        return $columns;
    }

    /**
     * @param  array<int, string>  $values
     * @param  array<string, int>  $columns
     * @return array<string, string>
     */
    private function mapRow(array $values, array $columns): array
    {
        $row = [];

        foreach ($columns as $key => $index) {
            $row[$key] = trim((string) ($values[$index] ?? ''));
        }

        return $row;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        return trim(implode('', $row)) === '';
    }

    private function detectDelimiter(string $header): string
    {
        $delimiters = [',' => substr_count($header, ','), ';' => substr_count($header, ';'), "\t" => substr_count($header, "\t")];
        arsort($delimiters);

        return (string) array_key_first($delimiters);
    }

    private function parseType(string $value): ?string
    {
        return self::TYPE_ALIASES[$this->normalize($value)] ?? null;
    }

    private function canonicalRecipeName(string $value): string
    {
        return self::RECIPE_NAME_ALIASES[$this->normalize($value)] ?? $value;
    }

    private function parseUnit(string $value): string
    {
        $unit = mb_strtolower(trim($value));

        return match ($unit) {
            'litri', 'litru' => 'l',
            'bucati', 'bucata' => 'buc',
            'grame', 'gram' => 'g',
            default => $unit,
        };
    }

    private function parseNumber(?string $value): ?float
    {
        $raw = $this->rawNumber($value);

        if ($raw === '') {
            return null;
        }

        $raw = str_replace([' ', 'RON', 'ron'], '', $raw);

        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            if (strrpos($raw, ',') > strrpos($raw, '.')) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } else {
            $raw = str_replace(',', '.', $raw);
        }

        return is_numeric($raw) ? (float) $raw : null;
    }

    private function rawNumber(?string $value): string
    {
        return trim((string) $value);
    }

    private function normalize(string $value): string
    {
        $value = Str::ascii($value);
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    public function ingredientKey(string $name, string $unit): string
    {
        return $this->normalize($name).'|'.$unit;
    }

    private function columnIndex(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference)) ?? '';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        $value = (string) $cell->v;

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'inlineStr') {
            return $this->richText($cell);
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');

        if ($content === false) {
            return [];
        }

        $xml = @simplexml_load_string($content);

        if ($xml === false) {
            return [];
        }

        $strings = [];

        foreach ($xml->si as $string) {
            $strings[] = $this->richText($string);
        }

        return $strings;
    }

    private function richText(SimpleXMLElement $node): string
    {
        $text = '';
        $parts = $node->xpath('.//*[local-name()="t"]') ?: [];

        foreach ($parts as $part) {
            $text .= (string) $part;
        }

        return $text;
    }

    private function sheetPath(ZipArchive $zip, string $preferredName): string
    {
        $workbookContent = $zip->getFromName('xl/workbook.xml');
        $relationshipsContent = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookContent === false || $relationshipsContent === false) {
            throw new InvalidArgumentException('Fisierul XLSX nu are structura asteptata.');
        }

        $workbook = @simplexml_load_string($workbookContent);
        $relationships = @simplexml_load_string($relationshipsContent);

        if ($workbook === false || $relationships === false) {
            throw new InvalidArgumentException('Fisierul XLSX nu poate fi interpretat.');
        }

        $relationshipMap = [];

        foreach ($relationships->Relationship as $relationship) {
            $relationshipMap[(string) $relationship['Id']] = (string) $relationship['Target'];
        }

        $sheet = null;

        foreach ($workbook->sheets->sheet as $candidate) {
            if ($this->normalize((string) $candidate['name']) === $this->normalize($preferredName)) {
                $sheet = $candidate;

                break;
            }
        }

        if ($sheet === null) {
            $sheet = $workbook->sheets->sheet[0] ?? null;
        }

        if ($sheet === null) {
            throw new InvalidArgumentException('Fisierul XLSX nu contine nicio foaie.');
        }

        $relationshipId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        $target = ltrim($relationshipMap[$relationshipId] ?? '', '/');

        if ($target === '') {
            throw new InvalidArgumentException('Foaia de retete nu a fost gasita in fisierul XLSX.');
        }

        return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
    }
}
