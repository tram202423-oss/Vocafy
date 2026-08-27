<?php

namespace App\Exports;

use App\Models\Vocabulary;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VocabularyExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Vocabulary::query()->with('topic');
    }

    public function headings(): array
    {
        return [
            'word',
            'pronunciation',
            'meaning',
            'example',
            'level',
            'topic_slug',
        ];
    }

    public function map($vocabulary): array
    {
        return [
            $vocabulary->word,
            $vocabulary->pronunciation,
            $vocabulary->meaning,
            $vocabulary->example,
            $vocabulary->level,
            $vocabulary->topic?->slug,
        ];
    }
}
