<?php

namespace App\Models;

use Database\Factories\DonationFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    public const STATUS_PROMISED = 'promisa';

    public const STATUS_RECEIVED = 'primita';

    public const STATUS_USED = 'folosita';

    public const STATUS_CANCELLED = 'anulata';

    public const KIND_FOOD = 'alimente';

    public const KIND_MONEY = 'bani';

    public const KIND_OTHER = 'altele';

    public const KIND_SWEET_SNACK = 'gustari_dulci';

    public const KIND_SAVORY_SNACK = 'gustari_sarate';

    public const KIND_FRUIT = 'fructe';

    public const KIND_DRINK = 'cafea_ceai';

    public const STORAGE_COLD = 'rece';

    public const STORAGE_DRY = 'uscat';

    public const STORAGE_FROZEN = 'congelat';

    public const STORAGE_OPTIONS = [
        self::STORAGE_COLD => 'La rece',
        self::STORAGE_DRY => 'La uscat',
        self::STORAGE_FROZEN => 'Congelat',
    ];

    /** Categoriile folosite in Planul de sprijin si in rezumatul antiduplicare. */
    public const CONTRIBUTION_CATEGORIES = [
        self::KIND_SWEET_SNACK => 'Gustări dulci',
        self::KIND_SAVORY_SNACK => 'Gustări sărate',
        self::KIND_FRUIT => 'Fructe',
        self::KIND_DRINK => 'Cafea/ceai',
        self::KIND_FOOD => 'Alimente',
        self::KIND_OTHER => 'Altele',
    ];

    protected $fillable = [
        'congregation_id',
        'donor_congregation_id',
        'daily_meal_id',
        'recorded_by',
        'status',
        'kind',
        'date',
        'donation_date',
        'description',
        'food_type',
        'ingredient_id',
        'quantity',
        'unit',
        'storage_method',
        'delivery_until',
        'amount_ron',
        'estimated_value',
        'donor_name',
        'donor_phone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'donation_date' => 'date',
            'delivery_until' => 'datetime',
            'quantity' => 'decimal:2',
            'amount_ron' => 'decimal:2',
            'estimated_value' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Donation $donation): void {
            $user = auth()->user();

            $donation->normalizeLegacyFields();

            if ($user?->isScopedToCongregation()) {
                if ($donation->exists && (int) $donation->getOriginal('congregation_id') !== (int) $user->congregation_id) {
                    throw new AuthorizationException('Poti gestiona doar donatiile congregatiei tale.');
                }

                $donation->congregation_id = $user->congregation_id;
            }

            $donation->recorded_by ??= $user?->id;

            if ($donation->congregation_id === null && $donation->daily_meal_id !== null) {
                $meal = DailyMeal::query()->with('contributingCongregations')->find($donation->daily_meal_id);
                $donation->congregation_id = ($meal?->contributingCongregations)?->first()?->id ?? $meal?->congregation_id;
            }

            $donation->donor_congregation_id ??= $donation->congregation_id;
        });
    }

    private function normalizeLegacyFields(): void
    {
        $this->status = match ($this->status) {
            'promised' => self::STATUS_PROMISED,
            'received' => self::STATUS_RECEIVED,
            null, '' => self::STATUS_PROMISED,
            default => $this->status,
        };

        $this->kind = match ($this->kind) {
            null, '' => self::KIND_FOOD,
            default => $this->kind,
        };

        if ($this->date === null && $this->donation_date !== null) {
            $this->date = $this->donation_date;
        }

        if ($this->donation_date === null && $this->date !== null) {
            $this->donation_date = $this->date;
        }

        if ($this->isDirty('estimated_value') && ! $this->isDirty('amount_ron')) {
            $this->amount_ron = $this->estimated_value;
        }

        if ($this->isDirty('amount_ron') && ! $this->isDirty('estimated_value')) {
            $this->estimated_value = $this->amount_ron;
        }

        $this->amount_ron ??= 0;
        $this->estimated_value ??= $this->amount_ron;
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function donorCongregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class, 'donor_congregation_id');
    }

    public function dailyMeal(): BelongsTo
    {
        return $this->belongsTo(DailyMeal::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isScopedToCongregation()) {
            $query->where('congregation_id', $user->congregation_id);
        }

        return $query;
    }

    public function scopeReceived(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_RECEIVED, self::STATUS_USED]);
    }

    public function scopePromised(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROMISED);
    }

    public function scopeDeductibleFromShoppingList(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_FOOD)
            ->where('status', self::STATUS_RECEIVED)
            ->whereNotNull('description')
            ->where('quantity', '>', 0);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_RECEIVED => 'Primită',
            self::STATUS_USED => 'Folosită',
            self::STATUS_CANCELLED => 'Anulată',
            default => 'Promisă',
        };
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            self::KIND_MONEY => 'Bani',
            self::KIND_SWEET_SNACK => 'Gustări dulci',
            self::KIND_SAVORY_SNACK => 'Gustări sărate',
            self::KIND_FRUIT => 'Fructe',
            self::KIND_DRINK => 'Cafea/ceai',
            self::KIND_OTHER => 'Altele',
            default => 'Alimente',
        };
    }

    public function contributionCategory(): ?string
    {
        return array_key_exists($this->kind, self::CONTRIBUTION_CATEGORIES)
            ? (string) $this->kind
            : null;
    }

    public function storageLabel(): ?string
    {
        return $this->storage_method === null
            ? null
            : (self::STORAGE_OPTIONS[$this->storage_method] ?? (string) $this->storage_method);
    }

    public function deliveryUntilLabel(): ?string
    {
        return $this->delivery_until?->translatedFormat('d.m H:i');
    }
}
