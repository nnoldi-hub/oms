<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    public const ROLES = [
        'admin',
        'coordinator',
        'construction',
        'kitchen',
        'supply_manager',
        'congregation_responsible',
        'project_supervisor',
    ];

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'congregation_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCoordinator(): bool
    {
        return $this->role === 'coordinator';
    }

    public function isConstructionTeam(): bool
    {
        return $this->role === 'construction';
    }

    public function isKitchenTeam(): bool
    {
        return $this->role === 'kitchen';
    }

    public function isSupplyManager(): bool
    {
        return $this->role === 'supply_manager';
    }

    public function isCongregationResponsible(): bool
    {
        return $this->role === 'congregation_responsible';
    }

    public function isProjectSupervisor(): bool
    {
        return $this->role === 'project_supervisor';
    }

    public function canManageSupply(): bool
    {
        return $this->isAdmin() || $this->isSupplyManager();
    }

    public function canManageContributions(): bool
    {
        return $this->canManageSupply() || $this->isCongregationResponsible();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, self::ROLES, true);
    }
}
