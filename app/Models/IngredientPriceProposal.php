<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientPriceProposal extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'ingredient_id',
        'user_id',
        'proposed_price',
        'store',
        'note',
        'status',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_price' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function approve(User $reviewer): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by_id' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->ingredient->update(['unit_price' => $this->proposed_price]);
    }

    public function reject(User $reviewer): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by_id' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }
}
