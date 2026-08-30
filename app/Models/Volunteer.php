<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Volunteer extends Model
{
    /** @use HasFactory<\Database\Factories\VolunteerFactory> */
    use HasFactory;

    protected $fillable = ['daily_meal_id', 'name', 'phone', 'role', 'has_allergies', 'allergy_details'];

    protected function casts(): array
    {
        return ['has_allergies' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Volunteer $volunteer): void {
            if ($volunteer->has_allergies && blank($volunteer->allergy_details)) {
                throw ValidationException::withMessages([
                    'allergy_details' => 'Detaliile alergiei sunt obligatorii.',
                ]);
            }
        });
    }

    public function dailyMeal(): BelongsTo
    {
        return $this->belongsTo(DailyMeal::class);
    }
}
