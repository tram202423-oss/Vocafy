<?php

namespace App\Rules;

use App\Services\IeltsAudioService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class ValidIeltsAudioLink implements ValidationRule
{
    public function __construct(private readonly bool $allowGoogleDrive = true)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        try {
            $service = app(IeltsAudioService::class);
            $service->validateLink(is_string($value) ? $value : '');
            if (! $this->allowGoogleDrive && $service->isGoogleDriveLink((string) $value)) {
                $fail('Dùng nút Dán link audio để nhập file Google Drive trước khi lưu.');
            }
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
