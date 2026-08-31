<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PublicCongregationWeekLink extends Model
{
    use HasFactory;

    protected $fillable = ['week_id', 'congregation_id', 'token'];

    protected static function booted(): void
    {
        static::creating(fn (self $link) => $link->token ??= (string) Str::uuid());
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }
}