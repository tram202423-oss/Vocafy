<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function vocabularies(): BelongsToMany
    {
        return $this->belongsToMany(
            Vocabulary::class,
            'lesson_vocabularies',
            'lesson_id',
            'vocabulary_id'
        )->withTimestamps();
    }
}
