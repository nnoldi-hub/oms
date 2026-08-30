<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

class Congregation extends Model
{
    /** @use HasFactory<\Database\Factories\CongregationFactory> */
    use HasFactory;

    protected $fillable = ['name', 'assistant_name', 'assistant_phone', 'assistant_email'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(Week::class);
    }

    public function dailyMeals(): HasMany
    {
        return $this->hasMany(DailyMeal::class);
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class)->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saving(function (Congregation $congregation): void {
            $user = auth()->user();

            if ($congregation->exists && $user?->isCoordinator() && $congregation->isDirty('name')) {
                throw ValidationException::withMessages([
                    'name' => 'Coordonatorul poate modifica doar retetele aprobate.',
                ]);
            }
        });
    }
}
