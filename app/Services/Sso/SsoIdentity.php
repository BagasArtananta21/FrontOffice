<?php

namespace App\Services\Sso;

final readonly class SsoIdentity
{
    public function __construct(
        public string $subject,
        public ?string $nip = null,
        public ?string $email = null,
        public ?string $name = null,
    ) {}
}
