<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IeltsBandScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill',
        'test_type',
        'raw_score',
        'band_score',
    ];

    protected function casts(): array
    {
        return [
            'raw_score' => 'integer',
            'band_score' => 'float',
        ];
    }

    public static function convert(string $skill, string $testType, int $rawScore): ?float
    {
        $clampedScore = max(0, min(40, $rawScore));

        $record = static::where('skill', $skill)
            ->where('test_type', $testType)
            ->where('raw_score', $clampedScore)
            ->first();

        return $record ? (float) $record->band_score : null;
    }
}
