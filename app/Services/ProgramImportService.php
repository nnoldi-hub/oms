<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\Week;
use App\Support\CongregationRole;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Importa programul congregatiilor din CSV.
 *
 * CSV-ul este sursa de adevar pentru congregatiile contribuitoare. Congregatia
 * care gatesc saptamana, meniurile, oamenii, voluntarii, costurile si celelalte
 * date ale zilei nu sunt modificate de import.
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
    public function plan(string $path, bool $removeEmptyDays = false): array
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
            $monday = $date->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
            $desired = [
                'line' => $line,
                'week_number' => (int) $weekNumber,
                'date' => $dateKey,
                'iso_week_start' => $monday,
                'contributingCongregations' => $names,
                'note' => $note,
            ];

            if (isset($desiredByDate[$dateKey])) {
                if ($desiredByDate[$dateKey]['week_number'] === $desired['week_number']
                    && $desiredByDate[$dateKey]['contributingCongregations'] === $desired['contributingCongregations']
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
                'start_date' => $monday,
                'include_saturday' => false,
            ];

            if ($weekMap[$week]['start_date'] !== $monday) {
                $conflicts[] = "Saptamana {$week} din CSV acopera mai multe saptamani calendaristice ({$weekMap[$week]['start_date']} si {$monday}).";
            }

            if ($date->dayOfWeek === CarbonImmutable::SATURDAY) {
                $weekMap[$week]['include_saturday'] = true;
            }
        }

        ksort($desiredByDate);
        ksort($weekMap);

        $startDateOwners = [];

        foreach ($weekMap as $weekNumber => $weekData) {
            $start = $weekData['start_date'];

            if (isset($startDateOwners[$start]) && $startDateOwners[$start] !== $weekNumber) {
                $conflicts[] = "Data {$start} este folosita in CSV de saptamanile {$startDateOwners[$start]} si {$weekNumber}.";
            }

            $startDateOwners[$start] = $weekNumber;
        }

        $dbCongregations = Congregation::query()->pluck('id', 'name')->all();
        $weeksByNumber = Week::query()->get()->keyBy(fn (Week $week): int => (int) $week->week_number);
        $weeksByStartDate = Week::query()->get()->keyBy(fn (Week $week): string => $week->start_date->toDateString());
        $mealsByDate = DailyMeal::query()->with(['contributingCongregations', 'cookingCongregation', 'week'])->get()
            ->keyBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString());

        $congregationsToCreate = [];

        foreach ($desiredByDate as $desired) {
            foreach ($desired['contributingCongregations'] as $name) {
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

            if ($existing !== null && $sameStart !== null && (int) $existing->id !== (int) $sameStart->id) {
                $conflicts[] = "Saptamana {$weekNumber} si data {$weekData['start_date']} apartin unor inregistrari diferite din baza de date.";
            }

            if ($sameStart !== null && (int) $sameStart->week_number !== $weekNumber) {
                $conflicts[] = "Data {$weekData['start_date']} apartine deja saptamanii {$sameStart->week_number}, nu {$weekNumber}.";
            }

            $needsUpdate = $existing !== null
                && ($existing->start_date->toDateString() !== $weekData['start_date']
                    || (bool) $existing->include_saturday !== $weekData['include_saturday']);

            $weekPlan[] = [
                'id' => $existing?->id,
                'week_number' => $weekNumber,
                'start_date' => $weekData['start_date'],
                'include_saturday' => $weekData['include_saturday'],
                'action' => $existing === null ? 'create' : ($needsUpdate ? 'update' : 'ok'),
                'label' => "Saptamana {$weekNumber} · {$weekData['start_date']}".($weekData['include_saturday'] ? ' · cu sambata' : ''),
            ];
        }

        $nameToId = $dbCongregations;

        foreach ($congregationsToCreate as $name) {
            $nameToId[$name] = -1;
        }

        $dayPlan = [];
        $missingInDatabase = [];

        foreach ($desiredByDate as $desired) {
            $date = $desired['date'];
            $weekNumber = $desired['week_number'];
            $meal = $mealsByDate->get($date);
            $desiredIds = $this->contributorIds($desired['contributingCongregations'], $nameToId, $meal?->congregation_id);

            if ($meal === null) {
                $missingInDatabase[] = $date;
                $dayPlan[] = [
                    'id' => null,
                    'date' => $date,
                    'week_number' => $weekNumber,
                    'contributingCongregations' => $desired['contributingCongregations'],
                    'contributingCongregationIds' => $desiredIds,
                    'note' => $desired['note'],
                    'action' => 'create',
                    'label' => "{$date} · ".implode(', ', $desired['contributingCongregations']),
                ];

                continue;
            }

            $targetWeek = collect($weekPlan)->first(fn (array $week): bool => $week['week_number'] === $weekNumber);

            if ($meal->is_free_day) {
                $conflicts[] = "Ziua {$date} este marcata ca zi libera in baza de date, dar in CSV este programata.";
            }

            if ($targetWeek !== null && $targetWeek['id'] !== null && (int) $meal->week_id !== (int) $targetWeek['id']) {
                $conflicts[] = "Ziua {$date} apartine altei saptamani in baza de date decat saptamana {$weekNumber} din CSV.";
            }

            if ($this->hasConflictForDate($conflicts, $date)) {
                continue;
            }

            $currentIds = $meal->contributingCongregationIds();
            $needsContributorUpdate = $currentIds !== $desiredIds;
            $needsNoteUpdate = $desired['note'] !== '' && trim((string) $meal->contribution_notes) !== $desired['note'];

            if (! $needsContributorUpdate && ! $needsNoteUpdate) {
                continue;
            }

            $details = [];

            if ($needsContributorUpdate) {
                $details[] = 'contribuitori actualizati';
            }

            if ($needsNoteUpdate) {
                $details[] = 'nota actualizata';
            }

            $dayPlan[] = [
                'id' => $meal->id,
                'date' => $date,
                'week_number' => $weekNumber,
                'contributingCongregations' => $desired['contributingCongregations'],
                'contributingCongregationIds' => $desiredIds,
                'note' => $desired['note'],
                'action' => $needsNoteUpdate && ! $needsContributorUpdate ? 'update_note' : 'update',
                'label' => "{$date} · ".implode(', ', $desired['contributingCongregations']).' · '.implode(', ', $details),
            ];
        }

        $absentInDatabase = [];
        $emptyDaysToDelete = [];

        if ($desiredByDate !== []) {
            $firstDate = array_key_first($desiredByDate);
            $lastDate = array_key_last($desiredByDate);

            foreach ($mealsByDate as $date => $meal) {
                if ($date < $firstDate || $date > $lastDate || isset($desiredByDate[$date])) {
                    continue;
                }

                if ($removeEmptyDays && $this->isTrulyEmpty($meal)) {
                    $emptyDaysToDelete[] = [
                        'id' => $meal->id,
                        'date' => $date,
                        'label' => "{$date} · zi goala, fara date inregistrate",
                    ];

                    continue;
                }

                $absentInDatabase[] = $date;
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
            'emptyDaysToDelete' => $emptyDaysToDelete,
            'removeEmptyDays' => $removeEmptyDays,
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array{congregations: int, weeks: int, days: int, contributors: int, notes: int, empty_days: int}
     */
    public function apply(array $plan): array
    {
        return DB::transaction(function () use ($plan): array {
            $counts = [
                'congregations' => 0,
                'weeks' => 0,
                'days' => 0,
                'contributors' => 0,
                'notes' => 0,
                'empty_days' => 0,
            ];

            foreach ($plan['congregationsToCreate'] as $name) {
                Congregation::firstOrCreate(
                    ['name' => $name],
                    ['type' => CongregationRole::CONTRIBUTES],
                );
                $counts['congregations']++;
            }

            foreach ($plan['weeks'] as $week) {
                if ($week['action'] === 'create') {
                    Week::create([
                        'week_number' => $week['week_number'],
                        'start_date' => $week['start_date'],
                        'include_saturday' => $week['include_saturday'],
                    ]);
                    $counts['weeks']++;

                    continue;
                }

                if ($week['action'] === 'update') {
                    Week::query()->whereKey($week['id'])->update([
                        'start_date' => $week['start_date'],
                        'include_saturday' => $week['include_saturday'],
                    ]);
                    $counts['weeks']++;
                }
            }

            $congregationIds = Congregation::query()->pluck('id', 'name')->all();
            $weekIds = Week::query()->pluck('id', 'week_number')->all();

            foreach ($plan['days'] as $day) {
                $desiredIds = array_map(
                    fn (string $name): int => (int) ($congregationIds[$name] ?? 0),
                    $day['contributingCongregations'],
                );
                $desiredIds = array_values(array_filter($desiredIds, fn (int $id): bool => $id > 0));

                if ($day['action'] === 'create') {
                    $week = Week::query()->findOrFail((int) $weekIds[$day['week_number']]);

                    $meal = DailyMeal::create([
                        'meal_date' => $day['date'],
                        'week_id' => $week->id,
                        'congregation_id' => $week->congregation_id,
                        'contribution_notes' => $day['note'] !== '' ? $day['note'] : null,
                        'estimated_people' => 0,
                        'status' => 'draft',
                        'is_free_day' => false,
                    ]);
                    $meal->syncContributingCongregations($desiredIds);
                    $counts['days']++;
                    $counts['contributors']++;

                    continue;
                }

                $meal = DailyMeal::query()->findOrFail((int) $day['id']);

                if ($day['action'] === 'update' || $day['action'] === 'update_note') {
                    if ($day['action'] === 'update') {
                        $meal->syncContributingCongregations($desiredIds);
                        $counts['contributors']++;
                    }

                    if ($day['note'] !== '' && trim((string) $meal->contribution_notes) !== $day['note']) {
                        $meal->contribution_notes = $day['note'];
                        $meal->saveQuietly();
                        $counts['notes']++;
                    }
                }
            }

            foreach ($plan['emptyDaysToDelete'] as $emptyDay) {
                $this->deleteMeal(DailyMeal::query()->findOrFail((int) $emptyDay['id']));
                $counts['empty_days']++;
            }

            return $counts;
        });
    }

    /**
     * @param  list<string>  $names
     * @param  array<string, int>  $nameToId
     * @return list<int>
     */
    private function contributorIds(array $names, array $nameToId, mixed $cookingId): array
    {
        $cookingId = $cookingId === null ? null : (int) $cookingId;

        return collect($names)
            ->map(fn (string $name): int => (int) ($nameToId[$name] ?? 0))
            ->filter(fn (int $id): bool => $id > 0 && $id !== $cookingId)
            ->unique()
            ->values()
            ->all();
    }

    private function isTrulyEmpty(DailyMeal $meal): bool
    {
        if ($meal->is_free_day || $meal->is_cooked) {
            return false;
        }

        if ($meal->menu_id !== null
            || $meal->soup_menu_id !== null
            || $meal->dessert_menu_id !== null
            || $meal->actual_menu_id !== null
            || $meal->confirmed_people !== null
            || (int) $meal->estimated_people > 0
            || (int) $meal->maximum_budget > 0
            || $meal->actual_cost !== null
            || filled($meal->notes)
            || filled($meal->contribution_notes)) {
            return false;
        }

        if ($meal->contributingCongregations()->exists()
            || $meal->volunteers()->exists()
            || $meal->workTeams()->exists()
            || $meal->costSnapshot()->exists()
            || $meal->donations()->exists()
            || FoodFundTransaction::query()->where('daily_meal_id', $meal->id)->exists()) {
            return false;
        }

        $plan = $meal->supplyPlan;

        return $plan === null || ! $plan->consumptions()->exists();
    }

    private function deleteMeal(DailyMeal $meal): void
    {
        $meal->contributingCongregations()->detach();
        $meal->supplyPlan?->consumptions()->delete();
        $meal->supplyPlan?->delete();
        $meal->volunteers()->delete();
        $meal->workTeams()->delete();
        FoodFundTransaction::query()->where('daily_meal_id', $meal->id)->delete();
        $meal->delete();
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
