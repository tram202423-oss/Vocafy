<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function vocabularies()
    {
        return $this->belongsToMany(
            Vocabulary::class,
            'lesson_vocabularies',
            'lesson_id',
            'vocabulary_id'
        );
    }
}
