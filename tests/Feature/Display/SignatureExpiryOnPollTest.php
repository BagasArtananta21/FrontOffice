<?php

namespace Tests\Feature\Display;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class SignatureExpiryOnPollTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    private const NOW = '2026-10-15 10:00:00';

    private string $visitId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo(self::NOW);
        $this->withoutVite();
        $this->visitId = $this->makeWaitingVisit(['updated_at' => $this->secondsAgo(60)]);
    }

    private function secondsAgo(int $seconds): string
    {
        return now()->subSeconds($seconds)->format('Y-m-d H:i:s');
    }

    private function expire(): void
    {
        DB::table('kunjungan')->where('id', $this->visitId)->update(['updated_at' => $this->secondsAgo(301)]);
    }

    private function reasonOfVisit(): ?string
    {
        return $this->rawRow('kunjungan', $this->visitId)['alasan_tanpa_ttd'];
    }

    public function test_status_leaves_a_fresh_request_alone(): void
    {
        $this->asDisplay()->get(route('display.status'))
            ->assertOk()
            ->assertJsonPath('waiting_key', fn ($key) => str_starts_with($key, $this->visitId.'@'))
            ->assertSessionMissing('display_notice');

        $this->assertSame($this->visitId, $this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertNull($this->reasonOfVisit());
    }

    public function test_status_releases_an_expired_request_and_tells_the_display_to_warn_the_guest(): void
    {
        $this->expire();

        $this->asDisplay()->get(route('display.status'))
            ->assertOk()
            ->assertJson(['status' => 'ok', 'tampilkan_form' => false, 'waiting_key' => null])
            ->assertSessionHas('display_notice', 'timeout');

        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertSame('waktu_habis', $this->reasonOfVisit());
    }

    public function test_status_records_display_unavailable_when_the_display_had_been_disconnected(): void
    {
        $this->expire();
        $this->updateDevice(['terakhir_aktif' => $this->secondsAgo(120)]);

        $this->asDisplay()->get(route('display.status'))->assertOk()->assertSessionHas('display_notice', 'timeout');

        $this->assertSame('display_tidak_tersedia', $this->reasonOfVisit());
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_status_writes_the_heartbeat_after_releasing_the_request(): void
    {
        $this->expire();
        $this->updateDevice(['terakhir_aktif' => $this->secondsAgo(120)]);

        $this->asDisplay()->get(route('display.status'));

        $this->assertSame(self::NOW, $this->deviceRow()['terakhir_aktif']);
    }

    public function test_status_polling_twice_releases_only_once(): void
    {
        $this->expire();

        $this->asDisplay()->get(route('display.status'))->assertSessionHas('display_notice', 'timeout');
        $this->updateDevice(['terakhir_aktif' => $this->secondsAgo(120)]);
        $this->asDisplay()->get(route('display.status'))->assertOk();

        $this->assertSame('waktu_habis', $this->reasonOfVisit());
    }

    public function test_the_page_shows_the_summary_and_no_notice_for_a_fresh_request(): void
    {
        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary['Nama'] === 'Tamu Menunggu')
            ->assertViewHas('notice', null)
            ->assertViewHas('signatureSecondsLeft', 240);
    }

    public function test_the_page_releases_an_expired_request_and_shows_the_timeout_notice(): void
    {
        $this->expire();

        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('summary', null)
            ->assertViewHas('waitingKey', null)
            ->assertViewHas('notice', fn (array $notice) => $notice['title'] === 'Waktu tanda tangan habis');

        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertSame('waktu_habis', $this->reasonOfVisit());
    }

    public function test_the_page_shows_the_timeout_notice_when_the_status_poll_already_released_the_request(): void
    {
        $this->expire();
        $this->asDisplay()->get(route('display.status'));

        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('notice', fn (array $notice) => $notice['title'] === 'Waktu tanda tangan habis')
            ->assertViewHas('summary', null);
    }

    public function test_the_page_has_no_notice_on_an_ordinary_idle_load(): void
    {
        $this->updateDevice(['kunjungan_menunggu_id' => null]);

        $this->asDisplay()->get(route('display.show'))->assertOk()->assertViewHas('notice', null);
    }
}
