<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'super-admin';

    case ADMIN = 'admin';

    case EDITOR = 'editor';

    case MODERATOR = 'moderator';

    case USER = 'user';
}