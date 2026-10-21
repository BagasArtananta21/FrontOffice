<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SignatureImage implements ValidationRule
{
    private const PREFIX = 'data:image/png;base64,';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $binary = is_string($value) && str_starts_with($value, self::PREFIX)
            ? base64_decode(substr($value, strlen(self::PREFIX)), true)
            : false;

        if ($binary === false || ! str_starts_with($binary, "\x89PNG")) {
            $fail('Tanda tangan tidak valid. Silahkan ulangi.');
        }
    }
}