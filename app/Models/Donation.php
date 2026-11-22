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

    public const STATUS_PROMISED = 'promised';

    public const STATUS_RECEIVED = 'received';

    protected $fillable = [
        'congregation_id',
        'daily_meal_id',
        'recorded_by',
        'status',
        'donation_date',
        'food_type',
        'description',
        'quantity',
        'unit',
        'estimated_value',
        'donor_name',
        'donor_phone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'donation_date' => 'date',
            'quantity' => 'decimal:2',
            'estimated_value' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Donation $donation): void {
            $user = auth()->user();

            if ($user?->isScopedToCongregation()) {
                if ($donation->exists && (int) $donation->getOriginal('congregation_id') !== (int) $user->congregation_id) {
                    throw new AuthorizationException('Poti gestiona doar donatiile congregatiei tale.');
                }

                $donation->congregation_id = $user->congregation_id;
            }

            $donation->recorded_by ??= $user?->id;

            if ($donation->congregation_id === null && $donation->daily_meal_id !== null) {
                $meal = DailyMeal::query()->with('congregations')->find($donation->daily_meal_id);
                $donation->congregation_id = ($meal?->congregations)?->first()?->id ?? $meal?->congregation_id;
            }
        });
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function dailyMeal(): BelongsTo
    {
        return $this->belongsTo(DailyMeal::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
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
        return $query->where('status', self::STATUS_RECEIVED);
    }

    public function scopePromised(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROMISED);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_RECEIVED => 'Primită',
            default => 'Promisă',
        };
    }
}
