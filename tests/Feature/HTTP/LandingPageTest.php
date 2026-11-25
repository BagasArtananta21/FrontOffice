<?php

namespace Tests\Feature\HTTP;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_guest_sees_the_admin_and_guest_form_choices(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk sebagai Admin')
            ->assertSee('Formulir Tamu')
            ->assertSee(route('login'), false)
            ->assertSee(route('display.pair'), false);
    }

    public function test_a_stale_display_cookie_shows_the_landing_page(): void
    {
        $this->withCookie(\App\Models\DisplayDevice::COOKIE, 'token-yang-sudah-tidak-ada')
            ->get('/')
            ->assertOk()
            ->assertSee(route('display.pair'), false);
    }

    public function test_a_signed_in_officer_is_sent_to_the_admin_panel(): void
    {
        $this->actingAs($this->makeUser($this->makeOpd()))
            ->get('/')
            ->assertRedirect(url('/admin'));
    }

    public function test_a_signed_in_super_admin_is_sent_to_the_super_admin_panel(): void
    {
        $this->actingAs($this->makeUser(null, role: 'super_admin'))
            ->get('/')
            ->assertRedirect(url('/super-admin'));
    }
}