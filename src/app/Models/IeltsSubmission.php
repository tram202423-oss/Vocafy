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
        'ai_band_score',
        'teacher_band_score',
        'teacher_scored_at',
        'examiner_notes',
        'metadata', 'assigned_examiner_id', 'teacher_scored_by', 'teacher_criteria', 'grading_history', 'save_revision',
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
            'ai_band_score' => 'float',
            'teacher_band_score' => 'float',
            'teacher_scored_at' => 'datetime',
            'metadata' => 'array', 'teacher_criteria' => 'array', 'grading_history' => 'array', 'save_revision' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $submission): void {
            if (! in_array($submission->skill, ['writing', 'speaking'], true)) {
                return;
            }

            if ($submission->isDirty(['teacher_band_score', 'teacher_criteria', 'examiner_notes'])) {
                if ($submission->exists && $submission->status->value !== 'completed') {
                    throw \Illuminate\Validation\ValidationException::withMessages(['teacher_band_score' => 'Chỉ chấm điểm bài đã nộp.']);
                }
                $criteria = $submission->teacher_criteria ?? [];
                $sets = $submission->skill === 'writing' ? ['task1', 'task2'] : ['speaking'];
                $scores = [];
                foreach ($sets as $set) {
                    $values = array_filter($criteria[$set] ?? [], fn ($value) => $value !== null && $value !== '');
                    if ($values && count($values) !== 4) throw \Illuminate\Validation\ValidationException::withMessages(['teacher_criteria' => 'Nhập đủ bốn tiêu chí cho mỗi phần.']);
                    foreach ($values as $value) static::validateTeacherBand($value);
                    if ($values) $scores[$set] = array_sum($values) / 4;
                }
                if ($scores) {
                    if (count($scores) !== count($sets)) throw \Illuminate\Validation\ValidationException::withMessages(['teacher_criteria' => 'Nhập đủ tiêu chí của cả hai Task.']);
                    $overall = $submission->skill === 'writing' ? ($scores['task1'] + 2 * $scores['task2']) / 3 : $scores['speaking'];
                    $submission->teacher_band_score = round($overall * 2) / 2;
                }
                if ($submission->teacher_band_score !== null) static::validateTeacherBand($submission->teacher_band_score);
                $submission->teacher_scored_at = $submission->teacher_band_score !== null ? now() : null;
                $submission->teacher_scored_by = auth()->id();
                $history = $submission->grading_history ?? [];
                $history[] = [
                    'by' => auth()->id(), 'at' => now()->toIso8601String(),
                    'previous_band' => $submission->getOriginal('teacher_band_score'),
                    'band' => $submission->teacher_band_score, 'criteria' => $criteria,
                    'notes' => $submission->examiner_notes,
                ];
                $submission->grading_history = $history;
            }
            $submission->band_score = $submission->teacher_band_score;
        });
    }

    private static function validateTeacherBand(mixed $band): void
    {
        if (! is_numeric($band) || $band < 0 || $band > 9 || abs($band * 2 - round($band * 2)) > 0.00001) {
            throw \Illuminate\Validation\ValidationException::withMessages(['teacher_band_score' => 'Band phải từ 0 đến 9, theo bước 0.5.']);
        }
    }

    public function assignedExaminer(): BelongsTo { return $this->belongsTo(User::class, 'assigned_examiner_id'); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_scored_by'); }

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
