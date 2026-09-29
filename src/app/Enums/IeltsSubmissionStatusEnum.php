<?php

namespace App\Enums;

enum IeltsSubmissionStatusEnum: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case ABANDONED = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'Đang thi',
            self::COMPLETED => 'Đã hoàn thành',
            self::ABANDONED => 'Đã hủy',
        };
    }
}
