<?php

namespace App\Models;

use App\Enums\IeltsTestTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class IeltsTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'description',
        'duration_minutes',
        'is_published',
        'total_questions',
    ];

    protected function casts(): array
    {
        return [
            'type' => IeltsTestTypeEnum::class,
            'is_published' => 'boolean',
            'duration_minutes' => 'integer',
            'total_questions' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title) . '-' . Str::random(5);
            }
        });
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(IeltsSection::class, 'ielts_test_sections')
            ->withPivot('order')
            ->orderBy('ielts_test_sections.order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(IeltsSubmission::class);
    }
}
