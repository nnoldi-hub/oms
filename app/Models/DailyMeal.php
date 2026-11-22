<?php

namespace App\Models;

use App\Services\MenuSchedulingValidator;
use App\Support\CongregationAccess;
use Database\Factories\DailyMealFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class DailyMeal extends Model
{
    /** @use HasFactory<DailyMealFactory> */
    use HasFactory;

    protected $fillable = [
        'meal_date',
        'week_id',
        'congregation_id',
        'menu_id',
        'soup_menu_id',
        'dessert_menu_id',
        'estimated_people',
        'confirmed_people',
        'maximum_budget',
        'contributor_count',
        'notes',
        'delivery_time',
        'public_token',
        'status',
        'is_free_day',
        'is_cooked',
        'actual_cost',
        'actual_menu_id',
    ];

    protected function casts(): array
    {
        return [
            'meal_date' => 'date',
            'estimated_people' => 'integer',
            'confirmed_people' => 'integer',
            'maximum_budget' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'is_free_day' => 'boolean',
            'is_cooked' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DailyMeal $dailyMeal): void {
            $dailyMeal->public_token ??= (string) Str::uuid();
        });

        // Compatibilitate: codul care scrie inca doar coloana veche primeste
        // automat prima congregatie in pivot.
        static::created(function (DailyMeal $dailyMeal): void {
            if ($dailyMeal->congregation_id !== null && ! $dailyMeal->congregations()->exists()) {
                $dailyMeal->congregations()->attach($dailyMeal->congregation_id, ['position' => 0]);
                $dailyMeal->unsetRelation('congregations');
            }
        });

        static::saving(function (DailyMeal $dailyMeal): void {
            $user = auth()->user();

            if ($dailyMeal->exists && $user !== null && count(array_diff_key($dailyMeal->getDirty(), array_flip(['updated_by', 'updated_at']))) > 0) {
                $dailyMeal->updated_by = $user->id;
            }

            // ⚠️ COREMPARE: Verificăm restricțiile de securitate doar dacă înregistrarea EXISTĂ deja în baza de date
            // Acest lucru lasă generatorul automat să creeze zilele goale fără să verifice rolurile utilizatorilor
            if ($dailyMeal->exists) {

                // 1. Restricții pentru Echipa de Construcții
                if ($user?->isConstructionTeam() && $dailyMeal->isDirty([
                    'meal_date',
                    'week_id',
                    'congregation_id',
                    'menu_id',
                    'soup_menu_id',
                    'dessert_menu_id',
                    'notes',
                    'maximum_budget',
                    'contributor_count',
                    'public_token',
                    'status',
                ])) {
                    throw new AuthorizationException('Echipa de constructii poate modifica doar numarul de persoane.');
                }

                // 2. Restricții pentru Coordonatorul de Congregație: organizeaza zilele in care
                // congregatia lui este una dintre cele trei (retete, portii, buget, observatii),
                // dar nu muta zilele si nu schimba congregatiile.
                if ($user?->isCoordinator()) {
                    if (! CongregationAccess::canSeeMeal($user, $dailyMeal) || $dailyMeal->isDirty([
                        'meal_date',
                        'week_id',
                        'congregation_id',
                        'public_token',
                        'status',
                    ])) {
                        throw new AuthorizationException('Coordonatorul poate organiza doar zilele congregatiei sale, fara a le muta.');
                    }
                }
            }

            $validator = app(MenuSchedulingValidator::class);

            if ($validator->scheduleChanged($dailyMeal)) {
                $validator->validate($dailyMeal);
            }
        });
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    /**
     * @deprecated Foloseste congregations(); coloana este pastrata doar pentru datele vechi.
     */
    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    /**
     * Cele trei congregatii ale zilei, in ordinea stabilita la programare.
     */
    public function congregations(): BelongsToMany
    {
        return $this->belongsToMany(Congregation::class, 'congregation_daily_meal')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('congregation_daily_meal.position')
            ->orderBy('congregations.id');
    }

    /** @return list<int> */
    public function congregationIds(): array
    {
        return $this->congregations->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
    }

    public function congregationNames(): string
    {
        return $this->congregations->pluck('name')->implode(', ');
    }

    public function otherCongregationNames(Congregation $congregation): string
    {
        return $this->congregations
            ->reject(fn (Congregation $candidate): bool => (int) $candidate->id === (int) $congregation->id)
            ->pluck('name')
            ->implode(', ');
    }

    /**
     * Inlocuieste complet congregatiile zilei, pastrand ordinea.
     *
     * @param  list<int>  $congregationIds
     */
    public function syncCongregations(array $congregationIds, ?User $actor = null): void
    {
        $ids = collect($congregationIds)
            ->filter(fn ($id): bool => $id !== null && $id !== '')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->congregations()->sync(
            collect($ids)->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]])->all(),
        );

        $this->congregation_id = $ids[0] ?? null;

        if ($actor !== null) {
            $this->updated_by = $actor->id;
        }

        $this->saveQuietly();
        $this->unsetRelation('congregations');
    }

    public function hasCongregation(?int $congregationId): bool
    {
        if ($congregationId === null) {
            return false;
        }

        return $this->congregations->contains(fn (Congregation $congregation): bool => (int) $congregation->id === $congregationId);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lastEditor(): ?User
    {
        return $this->updatedBy;
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function actualMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'actual_menu_id');
    }

    public function soupMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'soup_menu_id');
    }

    public function dessertMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'dessert_menu_id');
    }

    public function volunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class);
    }

    public function supplyPlan(): HasOne
    {
        return $this->hasOne(DailySupplyPlan::class);
    }

    public function costSnapshot(): HasOne
    {
        return $this->hasOne(DailyMealCostSnapshot::class);
    }

    public function workTeams(): HasMany
    {
        return $this->hasMany(WorkTeam::class);
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(MealChangeLog::class);
    }

    /**
     * Numarul de portii pe care se face calculul: confirmat daca exista, altfel estimat.
     */
    public function portions(): int
    {
        return max(0, (int) ($this->confirmed_people ?? $this->estimated_people));
    }

    /**
     * Portiile pe care se genereaza reteta si lista de cumparaturi: numarul de
     * persoane majorat cu marja de siguranta configurabila, rotunjit in sus.
     */
    public function bufferedPortions(): int
    {
        $portions = $this->portions();

        if ($portions <= 0) {
            return 0;
        }

        $margin = (float) config('oms.portion_safety_margin', 0.0);

        if ($margin <= 0) {
            return $portions;
        }

        return (int) ceil(round($portions * (1 + $margin), 6));
    }
}
