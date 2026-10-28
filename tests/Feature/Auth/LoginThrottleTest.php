<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(string $email, string $ip): TestResponse
    {
        return $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('login.store'), ['email' => $email, 'password' => 'salah-total']);
    }

    public function test_it_still_limits_attempts_per_ip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->attempt("user{$i}@example.com", '10.0.0.1')->assertStatus(302);
        }

        $this->attempt('user6@example.com', '10.0.0.1')->assertStatus(429);
    }

    public function test_it_limits_attempts_per_email_across_many_ips(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $email = $i % 2 === 0 ? 'TARGET@example.com' : 'target@example.com';

            $this->attempt($email, "10.0.1.{$i}")->assertStatus(302);
        }

        $this->attempt('target@example.com', '10.0.1.200')->assertStatus(429);
    }

    public function test_other_emails_are_not_affected_by_a_throttled_email(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->attempt('target@example.com', "10.0.2.{$i}");
        }

        $this->attempt('lain@example.com', '10.0.2.200')->assertStatus(302);
    }
}
