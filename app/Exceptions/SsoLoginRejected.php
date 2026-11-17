<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An SSO identity that may not sign in. The message is safe to show to the
 * user; `reason` is for code and logs.
 */
final class SsoLoginRejected extends RuntimeException
{
    public const REASON_NOT_REGISTERED = 'tidak_terdaftar';

    public const REASON_SUBJECT_CONFLICT = 'konflik_subject';

    public const REASON_USER_INACTIVE = 'user_nonaktif';

    public const REASON_OPD_INACTIVE = 'opd_nonaktif';

    private const MESSAGES = [
        self::REASON_NOT_REGISTERED => 'Akun SSO Anda belum terdaftar di aplikasi ini. Silakan hubungi administrator.',
        self::REASON_SUBJECT_CONFLICT => 'Akun Anda sudah terhubung dengan akun SSO lain. Silakan hubungi administrator.',
        self::REASON_USER_INACTIVE => 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator.',
        self::REASON_OPD_INACTIVE => 'OPD Anda sedang dinonaktifkan. Silakan hubungi administrator.',
    ];

    private function __construct(public readonly string $reason)
    {
        parent::__construct(self::MESSAGES[$reason]);
    }

    public static function notRegistered(): self
    {
        return new self(self::REASON_NOT_REGISTERED);
    }

    public static function subjectConflict(): self
    {
        return new self(self::REASON_SUBJECT_CONFLICT);
    }

    public static function userInactive(): self
    {
        return new self(self::REASON_USER_INACTIVE);
    }

    public static function opdInactive(): self
    {
        return new self(self::REASON_OPD_INACTIVE);
    }
}
