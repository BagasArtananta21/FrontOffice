<?php

namespace Tests\Feature\Display;

use App\Models\DisplayDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;
use Illuminate\Testing\TestResponse;

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

    private function pinError(): ?string
    {
        $session = app('session.store');
        $session->start();

        return $session->get('errors')?->first('pin');
    }


    public function test_the_pairing_page_is_open_without_a_cookie(): void
    {
        $this->withoutVite();

        $this->get(route('display.pair'))->assertOk();
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

    private function pair(?string $pin = self::DISPLAY_PIN): TestResponse
    {
        return $this->post(route('display.pair.store'), $pin === null ? [] : ['pin' => $pin]);
    }

    private function readyToPair(): void
    {
        $this->unpairDevice();
        $this->giveDevicePin();
    }

    public function test_a_valid_pin_pairs_an_unpaired_display_with_a_new_long_lived_token(): void
    {
        $this->readyToPair();

        $response = $this->pair();

        $response->assertRedirect(route('display.show'));
        $cookie = $response->getCookie(DisplayDevice::COOKIE);
        $token = $cookie->getValue();
        $this->assertSame(40, strlen($token));
        $this->assertNotSame(self::DISPLAY_PIN, $token);
        $this->assertSame(hash('sha256', $token), $this->deviceRow()['token_hash']);
        $this->assertGreaterThan(now()->addDays(300)->getTimestamp(), $cookie->getExpiresTime());

        $this->asDisplay($token)->get(route('display.status'))->assertOk();
    }

    public static function unusablePins(): array
    {
        return [
            'wrong pin' => ['000000'],
            'too short' => ['48291'],
            'too long' => ['4829130'],
            'letters' => ['abcdef'],
            'empty' => [''],
        ];
    }

    #[DataProvider('unusablePins')]
    public function test_an_unusable_pin_pairs_nothing(string $pin): void
    {
        $this->readyToPair();

        $this->pair($pin)
            ->assertSessionHasErrors('pin')
            ->assertCookieMissing(DisplayDevice::COOKIE);

        $this->assertNull($this->deviceRow()['token_hash']);
    }

    public function test_a_request_without_a_pin_is_rejected(): void
    {
        $this->readyToPair();

        $this->pair(null)->assertSessionHasErrors('pin');
    }

    public function test_a_correct_pin_is_refused_while_the_display_is_already_paired(): void
    {
        $this->giveDevicePin();

        $this->pair()
            ->assertSessionHasErrors('pin')
            ->assertCookieMissing(DisplayDevice::COOKIE);

        $this->assertSame(hash('sha256', self::DISPLAY_TOKEN), $this->deviceRow()['token_hash']);
    }

    public function test_a_wrong_pin_and_a_pin_in_use_get_the_same_message(): void
    {
        $this->giveDevicePin();

        $this->pair('000000')->assertSessionHasErrors('pin');
        $wrong = $this->pinError();

        $this->pair()->assertSessionHasErrors('pin');
        $inUse = $this->pinError();

        $this->assertNotNull($wrong);
        $this->assertSame($wrong, $inUse);
    }


    public function test_the_pin_is_not_kept_in_the_session(): void
    {
        $this->pair('000000')->assertSessionHasErrors('pin');

        $session = app('session.store');
        $session->start();
        $this->assertArrayNotHasKey('pin', $session->getOldInput());
    }

    public function test_pairing_again_after_unpairing_replaces_the_old_token(): void
    {
        $this->readyToPair();
        $first = $this->pair()->getCookie(DisplayDevice::COOKIE)->getValue();

        $this->unpairDevice();
        $second = $this->pair()->getCookie(DisplayDevice::COOKIE)->getValue();

        $this->assertNotSame($first, $second);
        $this->asDisplay($first)->get(route('display.status'))->assertForbidden();
        $this->asDisplay($second)->get(route('display.status'))->assertOk();
    }

    public function test_the_pin_of_a_deactivated_device_pairs_nothing(): void
    {
        $this->readyToPair();
        $this->updateDevice(['aktif' => false]);

        $this->pair()->assertSessionHasErrors('pin')->assertCookieMissing(DisplayDevice::COOKIE);
    }

    public function test_the_pin_of_a_device_whose_opd_is_inactive_pairs_nothing(): void
    {
        $this->readyToPair();
        $this->opd->forceFill(['aktif' => false])->save();

        $this->pair()->assertSessionHasErrors('pin')->assertCookieMissing(DisplayDevice::COOKIE);
    }

    public function test_pairing_is_throttled_to_five_attempts_a_minute_even_for_a_valid_pin(): void
    {
        $this->readyToPair();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->pair('00000'.$attempt)->assertStatus(302);
        }

        $this->pair()->assertStatus(429)->assertCookieMissing(DisplayDevice::COOKIE);
        $this->assertNull($this->deviceRow()['token_hash']);
    }

    public function test_the_throttle_window_reopens_after_a_minute(): void
    {
        $this->readyToPair();

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->pair('00000'.$attempt);
        }

        $this->travel(61)->seconds();

        $this->pair()->assertRedirect(route('display.show'));
    }

}
