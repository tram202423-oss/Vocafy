<?php

namespace App\Models;

use App\Enums\IeltsQuestionTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IeltsQuestionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'ielts_section_id',
        'title',
        'order',
        'passage_content',
        'audio_url',
        'transcript',
        'question_type',
        'instruction',
        'image_url',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'question_type' => IeltsQuestionTypeEnum::class,
            'order' => 'integer',
            'settings' => 'array',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(IeltsSection::class, 'ielts_section_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(IeltsQuestion::class)->orderBy('question_number');
    }
}
