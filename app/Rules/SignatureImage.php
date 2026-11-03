<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SignatureImage implements ValidationRule
{
    private const PREFIX = 'data:image/png;base64,';

    private const END_CHUNK = "\x00\x00\x00\x00IEND\xAE\x42\x60\x82";

    private const MAX_SIDE = 5120;

    private const MAX_PIXELS = 10_000_000;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isAcceptablePng($value)) {
            $fail('Tanda tangan tidak valid. Silakan ulangi.');
        }
    }

    private function isAcceptablePng(mixed $value): bool
    {
        $binary = is_string($value) && str_starts_with($value, self::PREFIX)
            ? base64_decode(substr($value, strlen(self::PREFIX)), true)
            : false;

        if ($binary === false || ! str_starts_with($binary, "\x89PNG") || ! str_ends_with($binary, self::END_CHUNK)) {
            return false;
        }

        $info = @getimagesizefromstring($binary);

        if ($info === false || $info[2] !== IMAGETYPE_PNG) {
            return false;
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width > self::MAX_SIDE || $height > self::MAX_SIDE || $width * $height > self::MAX_PIXELS) {
            return false;
        }

        // The size is checked first so a huge declared size is never decoded.
        return ! function_exists('imagecreatefromstring') || @imagecreatefromstring($binary) !== false;
    }
}
