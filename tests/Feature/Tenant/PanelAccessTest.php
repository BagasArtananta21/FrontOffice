<?php

namespace Tests\Feature\Tenant;

use App\Models\Opd;
use App\Services\AuthService;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    private const PASSWORD = 'kata-sandi-uji-123';

    public static function accounts(): array
    {
        // role, user active, opd state, can access admin panel, can access super-admin panel
        return [
            'active admin_fo of an active opd' => ['admin_fo', true, 'active', true, false],
            'admin_fo of an inactive opd' => ['admin_fo', true, 'inactive', false, false],
            'admin_fo without an opd' => ['admin_fo', true, 'none', false, false],
            'inactive admin_fo of an active opd' => ['admin_fo', false, 'active', false, false],
            'active super_admin' => ['super_admin', true, 'none', false, true],
            'inactive super_admin' => ['super_admin', false, 'none', false, false],
            'unknown role in an active opd' => ['staff', true, 'active', false, false],
        ];
    }

    private function opdFor(string $state): ?Opd
    {
        return match ($state) {
            'active' => $this->makeOpd('AKTIF'),
            'inactive' => $this->makeOpd('MATI', false),
            'none' => null,
        };
    }

    #[DataProvider('accounts')]
    public function test_it_grants_panel_access_by_role_user_state_and_opd_state(
        string $role,
        bool $userActive,
        string $opdState,
        bool $canAccessAdmin,
        bool $canAccessSuperAdmin,
    ): void {
        $user = $this->makeUser($this->opdFor($opdState), $role, $userActive);

        $this->assertSame($canAccessAdmin, $user->canAccessPanel(Filament::getPanel('admin')));
        $this->assertSame($canAccessSuperAdmin, $user->canAccessPanel(Filament::getPanel('super-admin')));
    }

    public function test_it_denies_every_panel_it_does_not_know(): void
    {
        $unknown = Panel::make()->id('lain');

        $this->assertFalse($this->makeUser($this->makeOpd('AKTIF'))->canAccessPanel($unknown));
        $this->assertFalse($this->makeUser(null, 'super_admin')->canAccessPanel($unknown));
    }

    public function test_it_tracks_the_opd_becoming_inactive_later(): void
    {
        $opd = $this->makeOpd('AKTIF');
        $user = $this->makeUser($opd);
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));

        $opd->forceFill(['aktif' => false])->save();

        $this->assertFalse($user->fresh()->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_attempt_accepts_an_active_admin_of_an_active_opd(): void
    {
        $user = $this->makeUser($this->makeOpd('AKTIF'));

        $this->assertTrue(app(AuthService::class)->attempt($user->email, self::PASSWORD));
        $this->assertSame($user->id, Auth::id());
    }

    public function test_attempt_accepts_a_super_admin_without_an_opd(): void
    {
        $user = $this->makeUser(null, 'super_admin');

        $this->assertTrue(app(AuthService::class)->attempt($user->email, self::PASSWORD));
        $this->assertSame($user->id, Auth::id());
    }

    public function test_attempt_rejects_an_inactive_user(): void
    {
        $user = $this->makeUser($this->makeOpd('AKTIF'), 'admin_fo', false);

        $this->assertFalse(app(AuthService::class)->attempt($user->email, self::PASSWORD));
        $this->assertGuest();
    }

    public function test_attempt_rejects_an_inactive_super_admin(): void
    {
        $user = $this->makeUser(null, 'super_admin', false);

        $this->assertFalse(app(AuthService::class)->attempt($user->email, self::PASSWORD));
        $this->assertGuest();
    }

    public function test_attempt_rejects_a_user_of_an_inactive_opd(): void
    {
        $user = $this->makeUser($this->makeOpd('MATI', false));

        $this->assertFalse(app(AuthService::class)->attempt($user->email, self::PASSWORD));
        $this->assertGuest();
    }

    public function test_attempt_rejects_an_admin_without_an_opd(): void
    {
        $user = $this->makeUser(null, 'admin_fo');

        $this->assertFalse(app(AuthService::class)->attempt($user->email, self::PASSWORD));
        $this->assertGuest();
    }

    public function test_attempt_rejects_a_wrong_password(): void
    {
        $user = $this->makeUser($this->makeOpd('AKTIF'));

        $this->assertFalse(app(AuthService::class)->attempt($user->email, 'bukan-kata-sandi-nya'));
        $this->assertGuest();
    }

    public function test_the_login_page_refuses_an_inactive_opd_and_sends_an_active_admin_to_the_admin_panel(): void
    {
        $inactive = $this->makeUser($this->makeOpd('MATI', false));
        $active = $this->makeUser($this->makeOpd('AKTIF'));

        $this->post(route('login.store'), ['email' => $inactive->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.store'), ['email' => $active->email, 'password' => self::PASSWORD])
            ->assertRedirect(Filament::getPanel('admin')->getUrl());
        $this->assertAuthenticatedAs($active);
    }

    public function test_a_super_admin_login_goes_to_the_super_admin_panel(): void
    {
        $superAdmin = $this->makeUser(null, 'super_admin');

        $this->post(route('login.store'), ['email' => $superAdmin->email, 'password' => self::PASSWORD])
            ->assertRedirect(Filament::getPanel('super-admin')->getUrl());
    }
}
