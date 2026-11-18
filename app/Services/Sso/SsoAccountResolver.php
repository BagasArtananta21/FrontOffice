<?php

namespace App\Services\Sso;

use App\Exceptions\SsoLoginRejected;
use App\Models\User;
use InvalidArgumentException;

class SsoAccountResolver
{
    /**
     * @throws SsoLoginRejected
     */
    public function resolve(SsoIdentity $identity): User
    {
        if (trim($identity->subject) === '') {
            throw new InvalidArgumentException('SSO subject must not be blank.');
        }

        $user = User::query()->where('sso_subject_id', $identity->subject)->first();

        if ($user !== null) {
            $this->ensureCanSignIn($user);

            return $user;
        }

        return $this->linkByNip($identity);
    }

    private function linkByNip(SsoIdentity $identity): User
    {
        // TODO (SSO HONORER): Staff without a NIP (honorer) currently are rejected as not registered.
        // Not Decided yet. Possible fallback: match by email, but only if SSO sends
        // a verified email (email_verified claim) that equals users.email exactly.
        if ($identity->nip === null || trim($identity->nip) === '') {
            throw SsoLoginRejected::notRegistered();
        }

        $user = User::query()->where('nip', trim($identity->nip))->first();

        if ($user === null) {
            throw SsoLoginRejected::notRegistered();
        }

        if ($user->sso_subject_id !== null) {
            throw SsoLoginRejected::subjectConflict();
        }

        $this->ensureCanSignIn($user);

        $linked = User::query()
            ->whereKey($user->getKey())
            ->whereNull('sso_subject_id')
            ->update(['sso_subject_id' => $identity->subject]);

        $user->refresh();

        if ($linked === 0 && $user->sso_subject_id !== $identity->subject) {
            throw SsoLoginRejected::subjectConflict();
        }

        return $user;
    }

    private function ensureCanSignIn(User $user): void
    {
        if (! $user->aktif) {
            throw SsoLoginRejected::userInactive();
        }

        // TODO (SSO SUPER ADMIN): Super Admin either can Sign in with SSO or not. if not then we need to check if the user is super admin and throw an exception.
        // Super Admin may sign in with SSO because they still part of ASN.
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->opd?->aktif !== true) {
            throw SsoLoginRejected::opdInactive();
        }

    }
}
