<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IeltsQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'ielts_question_group_id',
        'question_number',
        'order',
        'prompt',
        'explanation',
        'quote_reference',
        'options',
        'correct_answer',
        'word_limit',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'order' => 'integer',
            'options' => 'array',
            'word_limit' => 'integer',
            'points' => 'integer',
        ];
    }

    public function questionGroup(): BelongsTo
    {
        return $this->belongsTo(IeltsQuestionGroup::class, 'ielts_question_group_id');
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(IeltsUserAnswer::class);
    }
}
