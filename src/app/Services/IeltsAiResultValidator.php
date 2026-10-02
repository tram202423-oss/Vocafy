<?php

namespace App\Services;

use UnexpectedValueException;

class IeltsAiResultValidator
{
    public static function validate(array $result, string $skill): array
    {
        if ($skill === 'speaking' && ($result['gradable'] ?? true) === false) {
            if (! is_string($result['limitations'] ?? null) || trim($result['limitations']) === '') {
                throw new UnexpectedValueException('Ungradable audio requires a reason.');
            }
            return ['gradable' => false, 'overallScore' => null, 'criteria' => [],
                'transcript' => is_string($result['transcript'] ?? null) ? $result['transcript'] : '',
                'strengths' => [], 'improvements' => [], 'limitations' => $result['limitations']];
        }
        $validScore = fn ($score) => is_numeric($score) && is_finite((float) $score)
            && $score >= 0 && $score <= 9 && abs($score * 2 - round($score * 2)) < 0.00001;
        if (! $validScore($result['overallScore'] ?? null)
            || ! is_array($result['criteria'] ?? null) || count($result['criteria']) !== 4) {
            throw new UnexpectedValueException('Invalid IELTS band or criteria.');
        }
        foreach ($result['criteria'] as $criterion) {
            if (! is_array($criterion) || ! is_string($criterion['name'] ?? null)
                || ! is_string($criterion['comment'] ?? null) || ! $validScore($criterion['score'] ?? null)) {
                throw new UnexpectedValueException('Invalid IELTS criterion.');
            }
        }
        foreach (['strengths', 'improvements'] as $key) {
            if (! is_array($result[$key] ?? null)) throw new UnexpectedValueException('Invalid IELTS feedback.');
            foreach ($result[$key] as $text) {
                if (! is_string($text)) throw new UnexpectedValueException('Invalid IELTS feedback item.');
            }
        }
        foreach (['transcript', 'limitations', 'sampleEssay', 'level', 'summary', 'bandLevel', 'complexity'] as $key) {
            if (isset($result[$key]) && ! is_string($result[$key])) throw new UnexpectedValueException('Invalid IELTS report text.');
        }
        if (isset($result['corrections'])) {
            if (! is_array($result['corrections'])) throw new UnexpectedValueException('Invalid corrections.');
            foreach ($result['corrections'] as $correction) {
                if (! is_array($correction)) throw new UnexpectedValueException('Invalid correction.');
                foreach ($correction as $value) if (! is_scalar($value) && $value !== null) throw new UnexpectedValueException('Invalid correction value.');
            }
        }
        return $result;
    }
}
