<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeekChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'week_id',
        'congregation_id',
        'user_id',
        'reason',
        'preferred_week_id',
        'preferred_congregation_id',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferredWeek(): BelongsTo
    {
        return $this->belongsTo(Week::class, 'preferred_week_id');
    }

    public function preferredCongregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class, 'preferred_congregation_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Aprobata',
            self::STATUS_REJECTED => 'Respinsa',
            default => 'In asteptare',
        };
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isCoordinator()) {
            $query->where('congregation_id', $user->congregation_id);
        }

        return $query;
    }
}
