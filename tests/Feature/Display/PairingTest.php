<?php

namespace Tests\Feature\Display;

use App\Models\DisplayDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class PairingTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
    }

    public static function displayRoutes(): array
    {
        return [
            'show' => ['get', 'display.show'],
            'status' => ['get', 'display.status'],
            'confirmation' => ['get', 'display.confirmation'],
            'store visit' => ['post', 'display.kunjungan.store'],
            'store signature' => ['post', 'display.signature.store'],
        ];
    }

    public function test_the_pairing_page_is_open_without_a_cookie(): void
    {
        $this->withoutVite();

        $this->get(route('display.pair'))->assertOk();
    }

    public function test_a_valid_token_sets_a_long_lived_encrypted_cookie_and_goes_to_the_display(): void
    {
        $response = $this->post(route('display.pair.store'), ['token' => self::DISPLAY_TOKEN]);

        $response->assertRedirect(route('display.show'));
        $response->assertCookie(DisplayDevice::COOKIE, self::DISPLAY_TOKEN);
        $this->assertGreaterThan(now()->addDays(300)->getTimestamp(), $response->getCookie(DisplayDevice::COOKIE)->getExpiresTime());
        $this->assertNotSame(self::DISPLAY_TOKEN, $response->getCookie(DisplayDevice::COOKIE, false)->getValue());
    }

    public function test_the_token_is_stored_as_a_sha256_hash_and_the_hash_is_not_a_valid_token(): void
    {
        $this->assertSame(hash('sha256', self::DISPLAY_TOKEN), $this->deviceRow()['token_hash']);

        $this->post(route('display.pair.store'), ['token' => hash('sha256', self::DISPLAY_TOKEN)])
            ->assertSessionHasErrors('token')
            ->assertCookieMissing(DisplayDevice::COOKIE);
    }

    public static function unusableTokens(): array
    {
        return [
            'unknown token' => ['token-yang-salah'],
            'empty token' => [''],
            'token with different case' => [strtoupper(self::DISPLAY_TOKEN)],
        ];
    }

    #[DataProvider('unusableTokens')]
    public function test_it_rejects_a_token_that_does_not_match_any_device(string $token): void
    {
        $this->post(route('display.pair.store'), ['token' => $token])
            ->assertSessionHasErrors('token')
            ->assertCookieMissing(DisplayDevice::COOKIE);
    }

    public function test_it_rejects_a_request_without_a_token(): void
    {
        $this->post(route('display.pair.store'))->assertSessionHasErrors('token');
    }

    public function test_it_rejects_the_token_of_a_deactivated_device(): void
    {
        $this->updateDevice(['aktif' => false]);

        $this->post(route('display.pair.store'), ['token' => self::DISPLAY_TOKEN])
            ->assertSessionHasErrors('token')
            ->assertCookieMissing(DisplayDevice::COOKIE);
    }

    public function test_it_rejects_the_token_of_a_device_whose_opd_is_inactive(): void
    {
        $this->opd->forceFill(['aktif' => false])->save();

        $this->post(route('display.pair.store'), ['token' => self::DISPLAY_TOKEN])
            ->assertSessionHasErrors('token')
            ->assertCookieMissing(DisplayDevice::COOKIE);
    }

    public function test_pairing_is_throttled_to_five_attempts_a_minute_even_for_a_valid_token(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('display.pair.store'), ['token' => 'tebakan-'.$attempt])->assertStatus(302);
        }

        $this->post(route('display.pair.store'), ['token' => self::DISPLAY_TOKEN])
            ->assertStatus(429)
            ->assertCookieMissing(DisplayDevice::COOKIE);
    }

    public function test_a_valid_pairing_counts_towards_the_throttle_too(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('display.pair.store'), ['token' => self::DISPLAY_TOKEN])->assertRedirect(route('display.show'));
        }

        $this->post(route('display.pair.store'), ['token' => self::DISPLAY_TOKEN])->assertStatus(429);
    }

    public function test_the_throttle_window_reopens_after_a_minute(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post(route('display.pair.store'), ['token' => 'tebakan-'.$attempt]);
        }

        $this->travel(61)->seconds();

        $this->post(route('display.pair.store'), ['token' => self::DISPLAY_TOKEN])->assertRedirect(route('display.show'));
    }

    #[DataProvider('displayRoutes')]
    public function test_display_routes_refuse_a_browser_without_the_cookie(string $method, string $route): void
    {
        $this->{$method}(route($route))->assertForbidden();
    }

    #[DataProvider('displayRoutes')]
    public function test_display_routes_refuse_an_unknown_token(string $method, string $route): void
    {
        $this->asDisplay('token-yang-salah')->{$method}(route($route))->assertForbidden();
    }

    #[DataProvider('displayRoutes')]
    public function test_display_routes_refuse_the_stored_hash_used_as_a_token(string $method, string $route): void
    {
        $this->asDisplay(hash('sha256', self::DISPLAY_TOKEN))->{$method}(route($route))->assertForbidden();
    }

    #[DataProvider('displayRoutes')]
    public function test_display_routes_refuse_a_deactivated_device(string $method, string $route): void
    {
        $this->updateDevice(['aktif' => false]);

        $this->asDisplay()->{$method}(route($route))->assertForbidden();
    }

    #[DataProvider('displayRoutes')]
    public function test_display_routes_refuse_a_device_of_an_inactive_opd(string $method, string $route): void
    {
        $this->opd->forceFill(['aktif' => false])->save();

        $this->asDisplay()->{$method}(route($route))->assertForbidden();
    }

    public function test_a_paired_device_gets_through_and_the_cookie_is_renewed(): void
    {
        $this->asDisplay()->get(route('display.status'))
            ->assertOk()
            ->assertCookie(DisplayDevice::COOKIE, self::DISPLAY_TOKEN);
    }

    public function test_a_login_page_request_from_a_paired_display_is_sent_back_to_the_display(): void
    {
        $this->asDisplay()->get(route('login'))->assertRedirect(route('display.show'));
    }

    public function test_the_root_url_from_a_paired_display_is_sent_back_to_the_display(): void
    {
        $this->asDisplay()->get('/')->assertRedirect(route('display.show'));
    }

    public function test_a_login_attempt_from_a_paired_display_never_reaches_the_login_logic(): void
    {
        $this->asDisplay()
            ->post(route('login.store'), ['email' => $this->officer->email, 'password' => 'kata-sandi-uji-123'])
            ->assertRedirect(route('display.show'));

        $this->assertGuest();
    }

    public function test_the_same_requests_from_an_ordinary_browser_are_not_redirected_to_the_display(): void
    {
        $this->withoutVite();

        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }

    public function test_a_stale_cookie_does_not_block_the_login_page(): void
    {
        $this->withoutVite();

        $this->asDisplay('token-yang-salah')->get(route('login'))->assertOk();
        $this->updateDevice(['aktif' => false]);
        $this->asDisplay()->get(route('login'))->assertOk();
    }
}
