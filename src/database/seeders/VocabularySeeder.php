<?php

namespace Database\Seeders;

use App\Models\Topic;
use App\Models\Vocabulary;
use Illuminate\Database\Seeder;

class VocabularySeeder extends Seeder
{
    public function run(): void
    {
        $topic = Topic::where('slug', 'fruits')->first();

        $words = [
            [
                'word' => 'apple',
                'meaning' => 'quả táo',
                'pronunciation' => '/ˈæpl/',
                'example' => 'I eat an apple every day.',
            ],
            [
                'word' => 'banana',
                'meaning' => 'quả chuối',
                'pronunciation' => '/bəˈnænə/',
                'example' => 'Bananas are rich in potassium.',
            ],
            [
                'word' => 'orange',
                'meaning' => 'quả cam',
                'pronunciation' => '/ˈɔːrɪndʒ/',
                'example' => 'She drinks orange juice every morning.',
            ],
        ];

        foreach ($words as $word) {
            Vocabulary::firstOrCreate(
                ['word' => $word['word']],
                [
                    'topic_id' => $topic->id,
                    'meaning' => $word['meaning'],
                    'pronunciation' => $word['pronunciation'],
                    'example' => $word['example'],
                    'level' => 'easy',
                ]
            );
        }
    }
}