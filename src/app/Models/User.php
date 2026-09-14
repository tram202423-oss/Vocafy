<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userVocabularies()
    {
        return $this->hasMany(UserVocabulary::class);
    }

    public function vocabularies()
    {
        return $this->belongsToMany(
            Vocabulary::class,
            'user_vocabularies',
            'user_id',
            'vocabulary_id'
        )->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::ADMIN->value,
            RoleEnum::EDITOR->value,
            RoleEnum::MODERATOR->value,
        ]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::ADMIN->value,
        ]);
    }
    
    public function isCurrentUser(): bool
    {
        return auth()->id() === $this->id;
    }
}