<?php

namespace App\Services;

use App\Models\Congregation;

/**
 * Importa contactele congregatiilor dintr-un CSV incarcat din panou.
 *
 * Format: nume congregatie, persoana, telefon, email. Delimitatorul poate fi
 * virgula sau punct si virgura, iar primul rand poate fi un antet.
 */
class ContactImportService
{
    /**
     * @return array{rows: list<array<string, mixed>>, matched: int, missing: list<string>, changed: int, total: int}
     */
    public function parse(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', ltrim($content, "\xEF\xBB\xBF")) ?: [];
        $lines = array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));

        if ($lines === []) {
            return ['rows' => [], 'matched' => 0, 'missing' => [], 'changed' => 0, 'total' => 0];
        }

        $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';

        if ($this->looksLikeHeader($lines[0], $delimiter)) {
            array_shift($lines);
        }

        $byName = Congregation::query()->get()->mapWithKeys(
            fn (Congregation $congregation): array => [$this->normalizeName($congregation->name) => $congregation],
        );

        $rows = [];
        $missing = [];
        $matched = 0;
        $changed = 0;

        foreach ($lines as $line) {
            $cells = str_getcsv($line, $delimiter);
            $name = trim((string) ($cells[0] ?? ''));

            if ($name === '') {
                continue;
            }

            $person = trim((string) ($cells[1] ?? ''));
            $phone = trim((string) ($cells[2] ?? ''));
            $email = trim((string) ($cells[3] ?? ''));
            $congregation = $byName->get($this->normalizeName($name));

            $row = [
                'name' => $name,
                'person' => $person,
                'phone' => $phone,
                'email' => $email,
                'congregation' => $congregation,
                'status' => $congregation !== null ? 'matched' : 'missing',
            ];

            if ($congregation === null) {
                $missing[] = $name;
            } else {
                $matched++;

                if ($this->hasChanges($congregation, $row)) {
                    $changed++;
                }
            }

            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'matched' => $matched,
            'missing' => $missing,
            'changed' => $changed,
            'total' => count($rows),
        ];
    }

    /**
     * @param  array{rows: list<array<string, mixed>>, matched: int, missing: list<string>, changed: int, total: int}  $preview
     */
    public function summary(array $preview): string
    {
        return sprintf(
            'CSV: %d randuri, %d congregatii gasite, %d cu modificari, %d nume negasite.%s',
            $preview['total'],
            $preview['matched'],
            $preview['changed'],
            count($preview['missing']),
            $preview['missing'] !== [] ? ' Negasite: '.implode(', ', $preview['missing']).'.' : '',
        );
    }

    /**
     * @param  array{rows: list<array<string, mixed>>, matched: int, missing: list<string>, changed: int, total: int}  $preview
     */
    public function apply(array $preview): int
    {
        $saved = 0;

        foreach ($preview['rows'] as $row) {
            $congregation = $row['congregation'];

            if (! $congregation instanceof Congregation || ! $this->hasChanges($congregation, $row)) {
                continue;
            }

            $congregation->assistant_name = (string) $row['person'];
            $congregation->assistant_phone = (string) $row['phone'];
            $congregation->assistant_email = (string) $row['email'];
            $congregation->save();
            $saved++;
        }

        return $saved;
    }

    private function looksLikeHeader(string $line, string $delimiter): bool
    {
        $cells = array_map(
            fn ($cell): string => mb_strtolower(trim((string) $cell)),
            array_slice(str_getcsv($line, $delimiter), 0, 4),
        );
        $joined = implode('|', $cells);

        return (str_contains($joined, 'nume') || str_contains($joined, 'congregat'))
            && (str_contains($joined, 'persoan') || str_contains($joined, 'contact'))
            && (str_contains($joined, 'telefon') || str_contains($joined, 'phone'));
    }

    /** @param  array<string, mixed>  $row */
    private function hasChanges(Congregation $congregation, array $row): bool
    {
        return (string) $congregation->assistant_name !== (string) $row['person']
            || (string) $congregation->assistant_phone !== (string) $row['phone']
            || (string) $congregation->assistant_email !== (string) $row['email'];
    }

    private function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = strtr($name, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't',
        ]);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }
}
