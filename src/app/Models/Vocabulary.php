<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vocabulary extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'word',
        'pronunciation',
        'meaning',
        'example',
        'image',
        'audio',
        'level',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function lessons()
    {
        return $this->belongsToMany(
            Lesson::class,
            'lesson_vocabularies',
            'vocabulary_id',
            'lesson_id'
        );
    }

    public function userVocabularies()
    {
        return $this->hasMany(UserVocabulary::class);
    }

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Vocabulary',
                    'data' => [
                        Vocabulary::whereMonth('created_at', 1)->count(),
                        Vocabulary::whereMonth('created_at', 2)->count(),
                        Vocabulary::whereMonth('created_at', 3)->count(),
                        Vocabulary::whereMonth('created_at', 4)->count(),
                    ],
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr'],
        ];
    }
}
