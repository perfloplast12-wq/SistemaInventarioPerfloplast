<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, Auditable;

    protected string $auditModule = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'theme',
        'theme_color',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Ensure emails are stored lowercase.
     */
    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value ? strtolower($value) : null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }
}
