<?php

namespace Tests\Feature\Auth;

use App\Exceptions\SsoLoginRejected;
use App\Models\Opd;
use App\Models\User;
use App\Services\Sso\SsoAccountResolver;
use App\Services\Sso\SsoIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class SsoAccountResolverTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    private Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
    }

    private function resolver(): SsoAccountResolver
    {
        return app(SsoAccountResolver::class);
    }

    private function userWith(?string $nip, ?string $subject = null, bool $aktif = true, ?Opd $opd = null): User
    {
        $user = $this->makeUser($opd ?? $this->opd, aktif: $aktif);
        $user->forceFill(['nip' => $nip, 'sso_subject_id' => $subject])->save();

        return $user->refresh();
    }

    private function storedSubject(User $user): ?string
    {
        return $this->rawRow('users', $user->id)['sso_subject_id'];
    }

    private function assertRejected(SsoIdentity $identity, string $expectedReason): SsoLoginRejected
    {
        try {
            $user = $this->resolver()->resolve($identity);
        } catch (SsoLoginRejected $e) {
            $this->assertSame($expectedReason, $e->reason);
            $this->assertNotSame('', $e->getMessage());

            return $e;
        }

        $this->fail("Login SSO seharusnya ditolak ({$expectedReason}), tetapi menghasilkan user {$user->id}.");
    }

    public function test_it_matches_by_subject(): void
    {
        $user = $this->userWith(nip: '198701012010011001', subject: 'sub-pegawai');

        $resolved = $this->resolver()->resolve(new SsoIdentity('sub-pegawai'));

        $this->assertTrue($resolved->is($user));
        $this->assertSame('sub-pegawai', $this->storedSubject($user));
    }

    public function test_it_matches_by_nip_and_stores_the_subject(): void
    {
        $user = $this->userWith(nip: '199001012015031002');

        $resolved = $this->resolver()->resolve(new SsoIdentity('sub-baru', nip: '199001012015031002'));

        $this->assertTrue($resolved->is($user));
        $this->assertSame('sub-baru', $resolved->sso_subject_id);
        $this->assertSame('sub-baru', $this->storedSubject($user));
    }

    public function test_a_second_login_with_the_same_subject_matches_by_subject(): void
    {
        $user = $this->userWith(nip: '199001012015031002');
        $this->resolver()->resolve(new SsoIdentity('sub-baru', nip: '199001012015031002'));

        // Without a NIP the only way to find the user is the stored subject.
        $resolved = $this->resolver()->resolve(new SsoIdentity('sub-baru'));

        $this->assertTrue($resolved->is($user));
        $this->assertSame('sub-baru', $this->storedSubject($user));
    }

    public function test_an_unregistered_identity_is_rejected_without_creating_a_user(): void
    {
        $existing = $this->userWith(nip: '199001012015031002');
        $usersBefore = User::count();

        $this->assertRejected(
            new SsoIdentity('sub-asing', nip: '200001012020011999', email: 'asing@example.com', name: 'Orang Asing'),
            SsoLoginRejected::REASON_NOT_REGISTERED,
        );

        $this->assertSame($usersBefore, User::count());
        $this->assertDatabaseMissing('users', ['sso_subject_id' => 'sub-asing']);
        $this->assertDatabaseMissing('users', ['email' => 'asing@example.com']);
        $this->assertNull($this->storedSubject($existing));
    }

    public function test_an_unknown_subject_without_nip_is_rejected(): void
    {
        $existing = $this->userWith(nip: '199001012015031002');
        $usersBefore = User::count();

        $this->assertRejected(new SsoIdentity('sub-asing', nip: null), SsoLoginRejected::REASON_NOT_REGISTERED);

        $this->assertSame($usersBefore, User::count());
        $this->assertDatabaseMissing('users', ['sso_subject_id' => 'sub-asing']);
        $this->assertNull($this->storedSubject($existing));
    }

    public function test_a_nip_already_linked_to_another_subject_is_a_conflict(): void
    {
        $user = $this->userWith(nip: '199001012015031002', subject: 'sub-lama');

        $this->assertRejected(
            new SsoIdentity('sub-baru', nip: '199001012015031002'),
            SsoLoginRejected::REASON_SUBJECT_CONFLICT,
        );

        $this->assertSame('sub-lama', $this->storedSubject($user));
        $this->assertDatabaseMissing('users', ['sso_subject_id' => 'sub-baru']);
    }

    public function test_an_inactive_user_matched_by_subject_is_rejected(): void
    {
        $user = $this->userWith(nip: '199001012015031002', subject: 'sub-pegawai', aktif: false);

        $this->assertRejected(new SsoIdentity('sub-pegawai'), SsoLoginRejected::REASON_USER_INACTIVE);

        $this->assertSame('sub-pegawai', $this->storedSubject($user));
    }

    public function test_an_inactive_user_matched_by_nip_is_rejected_and_not_linked(): void
    {
        $user = $this->userWith(nip: '199001012015031002', aktif: false);

        $this->assertRejected(
            new SsoIdentity('sub-baru', nip: '199001012015031002'),
            SsoLoginRejected::REASON_USER_INACTIVE,
        );

        $this->assertNull($this->storedSubject($user));
    }

    public function test_a_user_of_an_inactive_opd_matched_by_subject_is_rejected(): void
    {
        $inactiveOpd = $this->makeOpd('MATI', aktif: false);
        $user = $this->userWith(nip: '199001012015031002', subject: 'sub-pegawai', opd: $inactiveOpd);

        $this->assertRejected(new SsoIdentity('sub-pegawai'), SsoLoginRejected::REASON_OPD_INACTIVE);

        $this->assertSame('sub-pegawai', $this->storedSubject($user));
    }

    public function test_a_user_of_an_inactive_opd_matched_by_nip_is_rejected_and_not_linked(): void
    {
        $inactiveOpd = $this->makeOpd('MATI', aktif: false);
        $user = $this->userWith(nip: '199001012015031002', opd: $inactiveOpd);

        $this->assertRejected(
            new SsoIdentity('sub-baru', nip: '199001012015031002'),
            SsoLoginRejected::REASON_OPD_INACTIVE,
        );

        $this->assertNull($this->storedSubject($user));
    }

    public function test_the_subject_wins_over_the_nip_when_they_point_to_different_users(): void
    {
        $bySubject = $this->userWith(nip: '198701012010011001', subject: 'sub-a');
        $byNip = $this->userWith(nip: '199001012015031002');

        $resolved = $this->resolver()->resolve(new SsoIdentity('sub-a', nip: '199001012015031002'));

        $this->assertTrue($resolved->is($bySubject));
        $this->assertSame('sub-a', $this->storedSubject($bySubject));
        $this->assertNull($this->storedSubject($byNip));
    }

    public function test_role_opd_and_profile_come_from_the_application_not_from_sso(): void
    {
        $user = $this->userWith(nip: '199001012015031002');
        $otherOpd = $this->makeOpd('LAIN');

        $resolved = $this->resolver()->resolve(new SsoIdentity(
            'sub-baru',
            nip: '199001012015031002',
            email: 'beda@example.com',
            name: 'Nama Dari SSO',
        ));

        $this->assertTrue($resolved->is($user));
        $this->assertSame('admin_fo', $resolved->role);
        $this->assertSame($this->opd->id, $resolved->opd_id);
        $this->assertNotSame($otherOpd->id, $resolved->opd_id);

        $row = $this->rawRow('users', $user->id);
        $this->assertSame($user->email, $row['email']);
        $this->assertSame($user->name, $row['name']);
        $this->assertSame('admin_fo', $row['role']);
        $this->assertSame($this->opd->id, $row['opd_id']);
        $this->assertSame('sub-baru', $row['sso_subject_id']);
    }

    public function test_a_blank_subject_is_refused_before_touching_the_database(): void
    {
        $user = $this->userWith(nip: '199001012015031002');

        try {
            $this->resolver()->resolve(new SsoIdentity('  ', nip: '199001012015031002'));
            $this->fail('Subject kosong seharusnya ditolak.');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertNull($this->storedSubject($user));
    }
}
