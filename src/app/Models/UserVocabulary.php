<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVocabulary extends Model
{
    protected $fillable = [
        'user_id',
        'vocabulary_id',
        'mastery_level',
        'correct_count',
        'wrong_count',
        'last_reviewed_at',
        'next_review_at',
    ];

    protected $casts = [
        'last_reviewed_at' => 'datetime',
        'next_review_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vocabulary()
    {
        return $this->belongsTo(Vocabulary::class);
    }
}
