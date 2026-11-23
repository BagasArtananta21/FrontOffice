<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class LoginTimestampTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    public function test_a_successful_login_records_the_time(): void
    {
        $user = $this->makeUser($this->makeOpd());
        $this->travelTo('2026-11-23 08:00:00');

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'kata-sandi-uji-123']);

        $this->assertSame('2026-11-23 08:00:00', $user->fresh()->terakhir_login_pada?->toDateTimeString());
    }

    public function test_a_failed_login_records_nothing(): void
    {
        $user = $this->makeUser($this->makeOpd());

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'salah-total']);

        $this->assertNull($user->fresh()->terakhir_login_pada);
    }
}
