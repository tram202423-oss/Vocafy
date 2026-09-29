<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IeltsUserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'ielts_submission_id',
        'ielts_question_id',
        'user_answer',
        'is_correct',
        'is_flagged_for_review',
        'time_spent_seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'is_flagged_for_review' => 'boolean',
            'time_spent_seconds' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(IeltsSubmission::class, 'ielts_submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(IeltsQuestion::class, 'ielts_question_id');
    }
}
