<?php

namespace Tests\Feature\Display;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class StoreVisitSuccessTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo('2026-10-15 09:30:00');
        $this->showForm();
    }

    private function onlyVisit(): object
    {
        $this->assertSame(1, DB::table('kunjungan')->count());

        return DB::table('kunjungan')->first();
    }

    public function test_it_stores_the_visit_with_the_system_columns_filled_by_the_server(): void
    {
        $this->submitVisit()->assertRedirect(route('display.confirmation'));

        $visit = $this->onlyVisit();
        $this->assertSame($this->opd->id, $visit->opd_id);
        $this->assertSame('display', $visit->sumber_input);
        $this->assertSame($this->officer->id, $visit->dicatat_oleh);
        $this->assertSame('20261015-0001', $visit->nomor_kunjungan);
        $this->assertSame('2026-10-15 09:30:00', $visit->waktu_datang);
        $this->assertNull($visit->alasan_tanpa_ttd);
        $this->assertNull($visit->catatan_petugas);
        $this->assertSame('Budi Santoso', $visit->nama_tamu);
        $this->assertSame('laki_laki', $visit->jenis_kelamin);
        $this->assertSame('Konsultasi', $visit->keperluan);
    }

    public function test_it_records_the_officer_who_last_pressed_show_form(): void
    {
        $newer = $this->makeUser($this->opd);
        $this->updateDevice(['ditampilkan_oleh' => $newer->id]);

        $this->submitVisit()->assertRedirect(route('display.confirmation'));

        $this->assertSame($newer->id, $this->onlyVisit()->dicatat_oleh);
    }

    public function test_it_stores_a_visit_even_when_no_officer_is_recorded_on_the_device(): void
    {
        $this->updateDevice(['ditampilkan_oleh' => null]);

        $this->submitVisit()->assertRedirect(route('display.confirmation'));

        $this->assertNull($this->onlyVisit()->dicatat_oleh);
    }

    public function test_it_ignores_system_columns_posted_by_the_browser(): void
    {
        $stranger = $this->makeUser($this->makeOpd('LAIN'));

        $this->submitVisit([
            'opd_id' => $this->newId(),
            'sumber_input' => 'frontoffice',
            'dicatat_oleh' => $stranger->id,
            'nomor_kunjungan' => 'MANUAL-9999',
            'waktu_datang' => '2020-01-01 00:00:00',
            'alasan_tanpa_ttd' => 'tamu_menolak',
            'catatan_petugas' => 'Disusupkan tamu',
            'id' => $this->newId(),
        ])->assertRedirect(route('display.confirmation'));

        $visit = $this->onlyVisit();
        $this->assertSame($this->opd->id, $visit->opd_id);
        $this->assertSame('display', $visit->sumber_input);
        $this->assertSame($this->officer->id, $visit->dicatat_oleh);
        $this->assertSame('20261015-0001', $visit->nomor_kunjungan);
        $this->assertSame('2026-10-15 09:30:00', $visit->waktu_datang);
        $this->assertNull($visit->alasan_tanpa_ttd);
        $this->assertNull($visit->catatan_petugas);
    }

    public function test_it_saves_the_signature_as_a_png_on_the_private_disk(): void
    {
        $this->submitVisit();

        $path = $this->onlyVisit()->tanda_tangan;
        $this->assertMatchesRegularExpression('#^tanda-tangan/2026/10/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.png$#', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame(base64_decode(substr(self::PNG, strlen('data:image/png;base64,'))), Storage::disk('local')->get($path));
        $this->assertSame([$path], Storage::disk('local')->allFiles());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_the_column_holds_only_the_path_and_never_the_image_data(): void
    {
        $this->submitVisit();

        $this->assertStringNotContainsString('base64', $this->onlyVisit()->tanda_tangan);
        $this->assertLessThan(100, strlen($this->onlyVisit()->tanda_tangan));
    }

    public function test_it_closes_the_form_after_a_successful_submit(): void
    {
        $this->submitVisit();

        $this->assertFalse((bool) $this->deviceRow()['tampilkan_form']);
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_successive_visits_get_successive_numbers(): void
    {
        $this->submitVisit(['nama_tamu' => 'Tamu Satu'])->assertRedirect(route('display.confirmation'));
        $this->showForm();
        $this->submitVisit(['nama_tamu' => 'Tamu Dua'])->assertRedirect(route('display.confirmation'));

        $numbers = DB::table('kunjungan')->orderBy('nomor_kunjungan')->pluck('nomor_kunjungan', 'nama_tamu')->all();

        $this->assertSame(['Tamu Satu' => '20261015-0001', 'Tamu Dua' => '20261015-0002'], $numbers);
    }

    public function test_the_confirmation_page_shows_a_thank_you_and_no_personal_data(): void
    {
        $this->withoutVite();
        $this->submitVisit([
            'nama_tamu' => 'Rahasia Sekali',
            'instansi_asal' => 'Instansi Pribadi',
            'no_hp' => '081234567890',
            'alamat' => 'Jalan Pribadi 7',
            'keperluan' => 'Urusan Pribadi',
        ])->assertRedirect(route('display.confirmation'));

        $this->asDisplay()->get(route('display.confirmation'))
            ->assertOk()
            ->assertSee('Terima kasih')
            ->assertDontSee('Rahasia Sekali')
            ->assertDontSee('Instansi Pribadi')
            ->assertDontSee('081234567890')
            ->assertDontSee('Jalan Pribadi 7')
            ->assertDontSee('Urusan Pribadi')
            ->assertDontSee('20261015-0001');
    }

    public function test_the_confirmation_page_sends_the_screen_back_to_idle_by_itself(): void
    {
        $this->withoutVite();

        $this->asDisplay()->get(route('display.confirmation'))
            ->assertOk()
            ->assertSee('http-equiv="refresh"', false)
            ->assertSee('url='.route('display.show'), false);
    }

    public function test_the_sixth_submit_within_a_minute_is_throttled(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->submitVisit()->assertStatus(302);
        }

        $this->submitVisit()->assertStatus(429);
    }

    // BUG: the `display-submit` limiter keys on request->attributes display_device, but the throttle middleware runs before EnsureDisplayDevice sets it, so the key is always the IP; displays behind one address share a single bucket.
    #[Group('known-bug')]
    public function test_the_throttle_counts_per_device_and_not_per_address(): void
    {
        $this->insertDisplay($this->opd->id, 'token-perangkat-kedua');

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->submitVisit();
        }

        $this->submitVisit(token: 'token-perangkat-kedua')->assertStatus(302);
    }

    public function test_the_throttle_follows_the_device_across_addresses(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->submitVisit();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->submitVisit()->assertStatus(429);
    }


    public function test_the_throttle_does_not_block_the_status_polling(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->submitVisit();
        }

        $this->asDisplay()->get(route('display.status'))->assertOk();
    }

    public function test_the_throttle_lets_the_device_submit_again_after_a_minute(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->submitVisit();
        }

        $this->travel(61)->seconds();
        $this->showForm();

        $this->submitVisit(['nama_tamu' => 'Tamu Setelah Menunggu'])->assertRedirect(route('display.confirmation'));
        $this->assertDatabaseHas('kunjungan', ['nama_tamu' => 'Tamu Setelah Menunggu']);
    }
}
