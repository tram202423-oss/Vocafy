<?php

namespace App\Enums;

enum IeltsSkillEnum: string
{
    case LISTENING = 'listening';
    case READING = 'reading';
    case WRITING = 'writing';
    case SPEAKING = 'speaking';

    public function label(): string
    {
        return match ($this) {
            self::LISTENING => 'Listening',
            self::READING => 'Reading',
            self::WRITING => 'Writing',
            self::SPEAKING => 'Speaking',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LISTENING => 'heroicon-o-speaker-wave',
            self::READING => 'heroicon-o-book-open',
            self::WRITING => 'heroicon-o-pencil-square',
            self::SPEAKING => 'heroicon-o-microphone',
        };
    }

    public function defaultMinutes(): int
    {
        return match ($this) {
            self::LISTENING => 30,
            self::READING => 60,
            self::WRITING => 60,
            self::SPEAKING => 15,
        };
    }
}
