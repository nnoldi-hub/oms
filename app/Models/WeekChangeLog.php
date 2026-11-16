<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeekChangeLog extends Model
{
    public const TYPE_MOVE = 'move';

    public const TYPE_SWAP = 'swap';

    protected $fillable = [
        'type',
        'week_id',
        'second_week_id',
        'from_congregation_id',
        'to_congregation_id',
        'user_id',
        'days_moved',
        'reason',
    ];

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    public function secondWeek(): BelongsTo
    {
        return $this->belongsTo(Week::class, 'second_week_id');
    }

    public function fromCongregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class, 'from_congregation_id');
    }

    public function toCongregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class, 'to_congregation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
