<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IeltsAnswerOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'ielts_question_group_id',
        'option_key',
        'label',
        'order',
    ];

    protected function casts(): array
    {
        return ['order' => 'integer'];
    }

    public function questionGroup(): BelongsTo
    {
        return $this->belongsTo(IeltsQuestionGroup::class, 'ielts_question_group_id');
    }
}
