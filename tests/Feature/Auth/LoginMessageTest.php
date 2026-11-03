<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failed_login_uses_the_same_term_as_the_password_field_label(): void
    {
        $this->post(route('login.store'), ['email' => 'tidak-ada@example.com', 'password' => 'salah-total'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    }
}
