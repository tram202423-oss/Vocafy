<?php

namespace App\Models;

use App\Enums\IeltsSubmissionStatusEnum;
use App\Enums\IeltsTestTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IeltsSubmission extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'ielts_test_id',
        'ielts_section_id',
        'skill',
        'test_type',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'raw_score',
        'total_questions',
        'band_score',
        'examiner_notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'test_type' => IeltsTestTypeEnum::class,
            'status' => IeltsSubmissionStatusEnum::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
            'raw_score' => 'integer',
            'total_questions' => 'integer',
            'band_score' => 'float',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(IeltsTest::class, 'ielts_test_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(IeltsSection::class, 'ielts_section_id');
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(IeltsUserAnswer::class);
    }
}
