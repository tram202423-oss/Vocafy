<?php

namespace Database\Seeders;

use App\Models\Topic;
use App\Models\Vocabulary;
use Illuminate\Database\Seeder;

class VocabularySeeder extends Seeder
{
    public function run(): void
    {
        $data = [

            'contracts' => [
                [
                    'word' => 'agreement',
                    'meaning' => 'thỏa thuận',
                    'pronunciation' => '/əˈɡriːmənt/',
                    'example' => 'They signed an agreement yesterday.',
                ],
                [
                    'word' => 'cancel',
                    'meaning' => 'hủy bỏ',
                    'pronunciation' => '/ˈkænsl/',
                    'example' => 'The company decided to cancel the contract.',
                ],
                [
                    'word' => 'establish',
                    'meaning' => 'thiết lập',
                    'pronunciation' => '/ɪˈstæblɪʃ/',
                    'example' => 'They established a new business.',
                ],
            ],

            'marketing' => [
                [
                    'word' => 'advertise',
                    'meaning' => 'quảng cáo',
                    'pronunciation' => '/ˈædvərtaɪz/',
                    'example' => 'The company advertises on television.',
                ],
                [
                    'word' => 'consumer',
                    'meaning' => 'người tiêu dùng',
                    'pronunciation' => '/kənˈsuːmər/',
                    'example' => 'Consumers want high-quality products.',
                ],
                [
                    'word' => 'attract',
                    'meaning' => 'thu hút',
                    'pronunciation' => '/əˈtrækt/',
                    'example' => 'Discounts attract customers.',
                ],
            ],
        ];

        foreach ($data as $topicSlug => $words) {

            $topic = Topic::where('slug', $topicSlug)->first();

            if (! $topic) {
                continue;
            }

            foreach ($words as $word) {

                Vocabulary::firstOrCreate(
                    [
                        'word' => $word['word'],
                    ],
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
}