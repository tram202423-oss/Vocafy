<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vocabulary extends Model
{
    protected $fillable = [
        'lesson_id',
        'word',
        'slug',
        'phonetic',
        'meaning',
        'meaning_en',
        'example',
        'example_vi',
        'audio',
        'image',
        'difficulty',
        'view_count',
        'is_active',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function examples()
    {
        return $this->hasMany(VocabularyExample::class);
    }
}