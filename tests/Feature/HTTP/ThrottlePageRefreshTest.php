<?php

namespace Tests\Feature\HTTP;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ThrottlePageRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function throttledResponse(?string $referer): TestResponse
    {
        $ip = ['REMOTE_ADDR' => '10.9.9.9'];

        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables($ip)
                ->post(route('login.store'), ['email' => "user{$i}@example.com", 'password' => 'salah-total']);
        }

        return $this->withServerVariables($ip)
            ->withHeaders($referer === null ? [] : ['Referer' => $referer])
            ->post(route('login.store'), ['email' => 'user6@example.com', 'password' => 'salah-total'])
            ->assertStatus(429);
    }

    private function refreshTarget(TestResponse $response): string
    {
        $this->assertSame(1, preg_match('/<meta http-equiv="refresh" content="\d+;url=([^"]*)">/', $response->getContent(), $matches));

        return html_entity_decode($matches[1]);
    }

    public function test_it_returns_to_a_page_of_the_same_site(): void
    {
        $target = $this->refreshTarget($this->throttledResponse(url('/display/pair')));

        $this->assertSame(url('/display/pair'), $target);
    }

    public function test_it_ignores_a_referer_from_another_site(): void
    {
        $target = $this->refreshTarget($this->throttledResponse('https://evil.example/phishing'));

        $this->assertSame(url('/'), $target);
    }

    public function test_it_ignores_a_referer_whose_host_only_starts_like_ours(): void
    {
        $target = $this->refreshTarget($this->throttledResponse(rtrim(url('/'), '/').'.evil.example/phishing'));

        $this->assertSame(url('/'), $target);
    }

    public function test_it_goes_home_when_there_is_no_referer(): void
    {
        $target = $this->refreshTarget($this->throttledResponse(null));

        $this->assertSame(url('/'), $target);
    }
}
