<?php

namespace App\Services;

use App\Models\IeltsSection;

class IeltsListeningMediaService
{
    public static function audioUrl(?IeltsSection $section): ?string
    {
        if (! $section) {
            return null;
        }

        if (array_key_exists('audio_url', $section->getAttributes())) {
            return filled($section->audio_url) ? $section->audio_url : null;
        }

        return $section->questionGroups->first(fn ($group) => filled($group->audio_url))?->audio_url;
    }

    public static function transcript(?IeltsSection $section): ?string
    {
        if (! $section) {
            return null;
        }

        if (array_key_exists('transcript', $section->getAttributes())) {
            return filled($section->transcript) ? $section->transcript : null;
        }

        $parts = $section->questionGroups
            ->pluck('transcript')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '');

        return $parts->isEmpty() ? null : $parts->implode("\n\n");
    }
}
