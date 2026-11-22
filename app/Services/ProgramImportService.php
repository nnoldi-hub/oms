<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Week;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Importa programul congregatiilor din CSV.
 *
 * CSV-ul este sursa de adevar: zilele sarbatorite si zilele lipsa din
 * programare sunt pastrate exact asa cum apar in fisier.
 */
class ProgramImportService
{
    private const HEADERS = [
        'saptamana',
        'data',
        'congregatia_1',
        'congregatia_2',
        'congregatia_3',
        'nota',
    ];

    /**
     * @return array<string, mixed>
     */
    public function plan(string $path): array
    {
        $rows = $this->read($path);

        $errors = [];
        $conflicts = [];
        $duplicates = [];
        $desiredByDate = [];
        $weekMap = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            $weekNumber = trim((string) ($row['saptamana'] ?? ''));
            $dateValue = trim((string) ($row['data'] ?? ''));
            $note = trim((string) ($row['nota'] ?? ''));
            $names = [
                trim((string) ($row['congregatia_1'] ?? '')),
                trim((string) ($row['congregatia_2'] ?? '')),
                trim((string) ($row['congregatia_3'] ?? '')),
            ];

            if ($weekNumber === '' || ! ctype_digit($weekNumber) || (int) $weekNumber < 1) {
                $errors[] = "Linia {$line}: saptamana trebuie sa fie un numar pozitiv.";

                continue;
            }

            $date = CarbonImmutable::createFromFormat('!Y-m-d', $dateValue);

            if ($date === false || $date->format('Y-m-d') !== $dateValue) {
                $errors[] = "Linia {$line}: data trebuie sa aiba formatul Y-m-d.";

                continue;
            }

            if (collect($names)->contains(fn (string $name): bool => $name === '')) {
                $errors[] = "Linia {$line}: toate cele trei congregatii sunt obligatorii.";

                continue;
            }

            if (count(array_unique($names)) !== 3) {
                $errors[] = "Linia {$line}: congregatiile unei zile trebuie sa fie diferite.";

                continue;
            }

            $dateKey = $date->toDateString();
            $desired = [
                'line' => $line,
                'week_number' => (int) $weekNumber,
                'date' => $dateKey,
                'congregations' => $names,
                'note' => $note,
            ];

            if (isset($desiredByDate[$dateKey])) {
                if ($desiredByDate[$dateKey]['week_number'] === $desired['week_number']
                    && $desiredByDate[$dateKey]['congregations'] === $desired['congregations']
                    && $desiredByDate[$dateKey]['note'] === $desired['note']) {
                    $duplicates[] = "Linia {$line}: aceeasi zi mai apare o data in CSV ({$dateKey}); inregistrarea duplicat este ignorata.";
                } else {
                    $conflicts[] = "Linia {$line}: data {$dateKey} apare de doua ori cu programari diferite.";
                }

                continue;
            }

            $desiredByDate[$dateKey] = $desired;

            $week = $desired['week_number'];
            $weekMap[$week] ??= [
                'week_number' => $week,
                'start_date' => $dateKey,
                'include_saturday' => false,
            ];

            if ($dateKey < $weekMap[$week]['start_date']) {
                $weekMap[$week]['start_date'] = $dateKey;
            }

            if ($date->dayOfWeek === CarbonImmutable::SATURDAY) {
                $weekMap[$week]['include_saturday'] = true;
            }
        }

        ksort($desiredByDate);
        ksort($weekMap);

        $dbCongregations = Congregation::query()->pluck('id', 'name')->all();
        $weeksByNumber = Week::query()->get()->keyBy(fn (Week $week): int => (int) $week->week_number);
        $weeksByStartDate = Week::query()->get()->keyBy(fn (Week $week): string => $week->start_date->toDateString());
        $mealsByDate = DailyMeal::query()->with('congregations')->get()
            ->keyBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString());

        $congregationsToCreate = [];

        foreach ($desiredByDate as $desired) {
            foreach ($desired['congregations'] as $name) {
                if (! isset($dbCongregations[$name]) && ! in_array($name, $congregationsToCreate, true)) {
                    $congregationsToCreate[] = $name;
                }
            }
        }

        sort($congregationsToCreate);

        $weekPlan = [];

        foreach ($weekMap as $weekNumber => $weekData) {
            $existing = $weeksByNumber->get($weekNumber);
            $sameStart = $weeksByStartDate->get($weekData['start_date']);

            if ($existing !== null && $existing->start_date->toDateString() !== $weekData['start_date']) {
                $conflicts[] = "Saptamana {$weekNumber} exista deja cu inceputul {$existing->start_date->toDateString()}, nu cu {$weekData['start_date']}.";
            }

            if ($sameStart !== null && (int) $sameStart->week_number !== $weekNumber) {
                $conflicts[] = "Data {$weekData['start_date']} apartine deja saptamanii {$sameStart->week_number}, nu {$weekNumber}.";
            }

            $needsUpdate = $existing !== null
                && ((bool) $existing->include_saturday !== $weekData['include_saturday'] || $existing->congregation_id !== null);

            $weekPlan[] = [
                'week_number' => $weekNumber,
                'start_date' => $weekData['start_date'],
                'include_saturday' => $weekData['include_saturday'],
                'action' => $existing === null ? 'create' : ($needsUpdate ? 'update' : 'ok'),
                'label' => "Saptamana {$weekNumber} · {$weekData['start_date']}".($weekData['include_saturday'] ? ' · cu sambata' : ''),
            ];
        }

        $dayPlan = [];
        $missingInDatabase = [];

        foreach ($desiredByDate as $desired) {
            $date = $desired['date'];
            $weekNumber = $desired['week_number'];
            $meal = $mealsByDate->get($date);
            $existingWeek = $weeksByNumber->get($weekNumber);

            if ($meal === null) {
                $missingInDatabase[] = $date;
                $dayPlan[] = [
                    'date' => $date,
                    'week_number' => $weekNumber,
                    'congregations' => $desired['congregations'],
                    'note' => $desired['note'],
                    'action' => 'create',
                    'label' => "{$date} · ".implode(', ', $desired['congregations']),
                ];

                continue;
            }

            $currentNames = $meal->congregations->pluck('name')->all();

            if ($meal->is_free_day) {
                $conflicts[] = "Ziua {$date} este marcata ca zi libera in baza de date, dar in CSV este programata.";
            }

            if ($existingWeek !== null && (int) $meal->week_id !== (int) $existingWeek->id) {
                $conflicts[] = "Ziua {$date} apartine altei saptamani in baza de date decat saptamana {$weekNumber} din CSV.";
            }

            if ($currentNames !== $desired['congregations']) {
                $conflicts[] = "Ziua {$date} are in baza de date congregatiile: ".($currentNames === [] ? 'none' : implode(', ', $currentNames)).'; in CSV: '.implode(', ', $desired['congregations']).'.';
            }

            $needsNoteUpdate = $desired['note'] !== '' && trim((string) $meal->notes) !== $desired['note'];

            if ($conflicts === [] || ! $this->hasConflictForDate($conflicts, $date)) {
                $dayPlan[] = [
                    'date' => $date,
                    'week_number' => $weekNumber,
                    'congregations' => $desired['congregations'],
                    'note' => $desired['note'],
                    'action' => $needsNoteUpdate ? 'update_note' : 'ok',
                    'label' => "{$date} · ".implode(', ', $desired['congregations']).($needsNoteUpdate ? ' · nota actualizata' : ''),
                ];
            }
        }

        $absentInDatabase = [];

        if ($desiredByDate !== []) {
            $firstDate = array_key_first($desiredByDate);
            $lastDate = array_key_last($desiredByDate);

            foreach ($mealsByDate as $date => $meal) {
                if ($date >= $firstDate && $date <= $lastDate && ! isset($desiredByDate[$date])) {
                    $absentInDatabase[] = $date;
                }
            }
        }

        return [
            'errors' => array_values(array_unique($errors)),
            'conflicts' => array_values(array_unique($conflicts)),
            'duplicates' => array_values(array_unique($duplicates)),
            'congregationsToCreate' => $congregationsToCreate,
            'weeks' => $weekPlan,
            'days' => $dayPlan,
            'missingInDatabase' => $missingInDatabase,
            'absentInDatabase' => $absentInDatabase,
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array{congregations: int, weeks: int, days: int, notes: int}
     */
    public function apply(array $plan): array
    {
        return DB::transaction(function () use ($plan): array {
            $counts = [
                'congregations' => 0,
                'weeks' => 0,
                'days' => 0,
                'notes' => 0,
            ];

            foreach ($plan['congregationsToCreate'] as $name) {
                Congregation::firstOrCreate(['name' => $name]);
                $counts['congregations']++;
            }

            foreach ($plan['weeks'] as $week) {
                if ($week['action'] === 'create') {
                    Week::create([
                        'week_number' => $week['week_number'],
                        'start_date' => $week['start_date'],
                        'include_saturday' => $week['include_saturday'],
                        'congregation_id' => null,
                    ]);
                    $counts['weeks']++;

                    continue;
                }

                if ($week['action'] === 'update') {
                    Week::query()->where('week_number', $week['week_number'])->update([
                        'include_saturday' => $week['include_saturday'],
                        'congregation_id' => null,
                    ]);
                    $counts['weeks']++;
                }
            }

            $congregationIds = Congregation::query()->pluck('id', 'name')->all();
            $weekIds = Week::query()->pluck('id', 'week_number')->all();

            foreach ($plan['days'] as $day) {
                if ($day['action'] === 'create') {
                    $ids = array_map(fn (string $name): int => (int) $congregationIds[$name], $day['congregations']);

                    $meal = DailyMeal::create([
                        'meal_date' => $day['date'],
                        'week_id' => (int) $weekIds[$day['week_number']],
                        'congregation_id' => $ids[0],
                        'notes' => $day['note'] !== '' ? $day['note'] : null,
                        'estimated_people' => 0,
                        'status' => 'draft',
                    ]);
                    $meal->syncCongregations($ids);
                    $counts['days']++;

                    continue;
                }

                if ($day['action'] === 'update_note') {
                    DailyMeal::query()
                        ->where('meal_date', $day['date'])
                        ->update(['notes' => $day['note']]);
                    $counts['notes']++;
                }
            }

            return $counts;
        });
    }

    /**
     * @return list<array<string, string>>
     */
    private function read(string $path): array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException("Fisierul nu poate fi citit: {$path}");
        }

        try {
            $firstLine = (string) fgets($handle);
            rewind($handle);
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

            $header = fgetcsv($handle, 0, $delimiter);

            if ($header === false) {
                throw new InvalidArgumentException('Fisierul CSV este gol.');
            }

            $header = array_map(
                fn (string $column): string => trim(str_replace("\u{FEFF}", '', $column)),
                $header,
            );

            if ($header !== self::HEADERS) {
                throw new InvalidArgumentException('Antetul CSV trebuie sa fie: '.implode(',', self::HEADERS));
            }

            $rows = [];

            while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count(array_filter($line, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $values = array_slice(array_pad($line, count($header), ''), 0, count($header));
                $rows[] = array_combine($header, $values);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string>  $conflicts
     */
    private function hasConflictForDate(array $conflicts, string $date): bool
    {
        foreach ($conflicts as $conflict) {
            if (str_contains($conflict, "Ziua {$date} ")) {
                return true;
            }
        }

        return false;
    }
}
