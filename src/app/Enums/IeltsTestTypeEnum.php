<?php

namespace App\Enums;

enum IeltsTestTypeEnum: string
{
    case ACADEMIC = 'academic';
    case GENERAL_TRAINING = 'general_training';

    public function label(): string
    {
        return match ($this) {
            self::ACADEMIC => 'Academic',
            self::GENERAL_TRAINING => 'General Training',
        };
    }
}
