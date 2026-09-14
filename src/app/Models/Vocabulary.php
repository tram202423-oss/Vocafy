<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(
            Lesson::class,
            'lesson_vocabularies',
            'vocabulary_id',
            'lesson_id'
        )->withTimestamps();
    }

    public function userVocabularies(): HasMany
    {
        return $this->hasMany(UserVocabulary::class);
    }

    /**
     * Get the highlighted example sentence with safe HTML escaping.
     */
    public function getHighlightedExampleAttribute(): ?string
    {
        if (empty($this->example)) {
            return null;
        }

        $escapedExample = e($this->example);
        $escapedWord = preg_quote(e($this->word), '/');

        return preg_replace(
            '/(' . $escapedWord . ')/i',
            '<strong class="vocab-target-word text-gray-900 not-italic font-bold">$1</strong>',
            $escapedExample
        );
    }
}
