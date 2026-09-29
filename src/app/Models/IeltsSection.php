<?php

namespace App\Models;

use App\Enums\IeltsSkillEnum;
use App\Enums\IeltsTestTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IeltsSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'skill',
        'test_type',
        'time_limit_minutes',
        'total_questions',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'skill' => IeltsSkillEnum::class,
            'test_type' => IeltsTestTypeEnum::class,
            'time_limit_minutes' => 'integer',
            'total_questions' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(IeltsTest::class, 'ielts_test_sections')
            ->withPivot('order');
    }

    public function questionGroups(): HasMany
    {
        return $this->hasMany(IeltsQuestionGroup::class)->orderBy('order');
    }

    public function questions()
    {
        return $this->hasManyThrough(IeltsQuestion::class, IeltsQuestionGroup::class)
            ->orderBy('ielts_questions.question_number');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(IeltsSubmission::class);
    }
}
