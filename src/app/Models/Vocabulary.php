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
}
