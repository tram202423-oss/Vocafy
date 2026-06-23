<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'super-admin';

    case ADMIN = 'admin';

    case EDITOR = 'editor';

    case MODERATOR = 'moderator';

    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::EDITOR => 'Editor',
            self::MODERATOR => 'Moderator',
            self::USER => 'User',
        };
    }
    
}