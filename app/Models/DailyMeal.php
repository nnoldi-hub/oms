<?php

namespace App\Models;

use App\Services\MenuSchedulingValidator;
use App\Support\CongregationAccess;
use App\Support\ContactStatus;
use Database\Factories\DailyMealFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
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
        'contribution_notes',
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

                // 2. Restricții pentru Coordonatorul de Congregație: organizeaza doar zilele
                // congregatiei care gatesc (retete, portii, buget, observatii), fara a le muta
                // si fara a schimba congregatiile.
                if ($user?->isCoordinator()) {
                    if (! CongregationAccess::canManageMeal($user, $dailyMeal) || $dailyMeal->isDirty([
                        'meal_date',
                        'week_id',
                        'congregation_id',
                        'public_token',
                        'status',
                    ])) {
                        throw new AuthorizationException('Coordonatorul poate organiza doar zilele congregatiei care gatesc, fara a le muta.');
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
     * Congregatia saptamanii: pregateste masa si ramane coloana principala a zilei.
     */
    public function cookingCongregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class, 'congregation_id');
    }

    /**
     * Alias pentru compatibilitate cu codul existent pe coloana congregation_id.
     */
    public function congregation(): BelongsTo
    {
        return $this->cookingCongregation();
    }

    /**
     * Congregatiile care aduc alimente in ziua respectiva, in ordinea din program.
     * Congregatia care gatesc nu face parte din aceasta lista.
     */
    public function contributingCongregations(): BelongsToMany
    {
        return $this->belongsToMany(Congregation::class, 'congregation_daily_meal')
            ->withPivot(['position', 'contact_status', 'contacted_at', 'contact_notes'])
            ->withTimestamps()
            ->orderBy('congregation_daily_meal.position')
            ->orderBy('congregations.id');
    }

    /** @return list<int> */
    public function contributingCongregationIds(): array
    {
        return $this->contributingCongregations
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function contributingCongregationNames(): string
    {
        $names = $this->contributingCongregations->pluck('name')->implode(', ');

        // Compatibilitate pentru zilele care inca nu au contribuitori inregistrati:
        // se afiseaza congregatia care gatesc, fara a o adauga in pivot.
        return $names !== '' ? $names : ($this->cookingCongregation?->name ?? '');
    }

    public function otherContributingCongregationNames(Congregation $congregation): string
    {
        return $this->contributingCongregations
            ->reject(fn (Congregation $candidate): bool => (int) $candidate->id === (int) $congregation->id)
            ->pluck('name')
            ->implode(', ');
    }

    /**
     * Inlocuieste complet congregatiile contribuitoare ale zilei, pastrand ordinea.
     * Congregatia care gatesc este intotdeauna exclusa si coloana congregation_id nu se modifica.
     *
     * @param  list<int>  $congregationIds
     */
    public function syncContributingCongregations(array $congregationIds, ?User $actor = null): void
    {
        $cookingId = $this->congregation_id === null ? null : (int) $this->congregation_id;

        $ids = collect($congregationIds)
            ->filter(fn ($id): bool => $id !== null && $id !== '')
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $id === $cookingId)
            ->unique()
            ->values()
            ->all();

        $this->contributingCongregations()->sync(
            collect($ids)->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]])->all(),
        );

        if ($actor !== null) {
            $this->updated_by = $actor->id;
            $this->saveQuietly();
        }

        $this->unsetRelation('contributingCongregations');
    }

    public function updateContributorContact(
        int $congregationId,
        string $status,
        ?string $notes = null,
        bool $markContactedNow = false,
    ): void {
        if (! array_key_exists($status, ContactStatus::options()) || ! $this->hasContributingCongregation($congregationId)) {
            return;
        }

        $attributes = ['contact_status' => $status];

        if ($notes !== null) {
            $attributes['contact_notes'] = mb_substr(trim($notes), 0, 500) ?: null;
        }

        if ($status === ContactStatus::NOT_CONTACTED) {
            $attributes['contacted_at'] = null;
        } elseif ($markContactedNow || $this->contributingCongregations()->whereKey($congregationId)->first()?->pivot->contacted_at === null) {
            $attributes['contacted_at'] = now();
        }

        $this->contributingCongregations()->updateExistingPivot($congregationId, $attributes);
        $this->unsetRelation('contributingCongregations');
    }

    public function hasContributingCongregation(?int $congregationId): bool
    {
        if ($congregationId === null) {
            return false;
        }

        return $this->contributingCongregations->contains(
            fn (Congregation $congregation): bool => (int) $congregation->id === $congregationId,
        );
    }

    public function hasCookingCongregation(?int $congregationId): bool
    {
        return $congregationId !== null && (int) $this->congregation_id === $congregationId;
    }

    /**
     * Zilele in care o congregatie gatesc sau contribuie.
     */
    public function scopeInvolvingCongregation(Builder $query, int $congregationId): Builder
    {
        return $query->where(function (Builder $scoped) use ($congregationId): void {
            $scoped->where('daily_meals.congregation_id', $congregationId)
                ->orWhereHas('contributingCongregations', fn (Builder $pivot) => $pivot->whereKey($congregationId));
        });
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

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
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
