<?php

namespace Tests\Feature\Tenant;

use App\Models\DisplayDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Support\BuildsTwoTenants;
use Tests\TestCase;

class DisplayTenantTest extends TestCase
{
    use BuildsTwoTenants, RefreshDatabase;

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seedTwoTenants();
    }

    private function deviceId(string $token): string
    {
        return (string) DB::table('display_devices')->where('token_hash', hash('sha256', $token))->value('id');
    }

    private function updateDevice(string $token, array $changes): void
    {
        DB::table('display_devices')->where('id', $this->deviceId($token))->update($changes);
    }

    private function showFormOn(string $token, string $officerId): void
    {
        $this->updateDevice($token, ['tampilkan_form' => true, 'ditampilkan_oleh' => $officerId]);
    }

    private function submitAs(string $token, array $overrides = []): TestResponse
    {
        return $this->withCookie(DisplayDevice::COOKIE, $token)->post(route('display.kunjungan.store'), [
            'nama_tamu' => 'Tamu Lintas OPD',
            'jenis_kelamin' => 'perempuan',
            'keperluan' => 'Uji isolasi',
            'tanda_tangan' => self::PNG,
            ...$overrides,
        ]);
    }

    private function bidangIdOf(\App\Models\Opd $opd, string $kode): string
    {
        return (string) DB::table('bidang')->where(['opd_id' => $opd->id, 'kode_bidang' => $kode])->value('id');
    }

    private function makeWaitingVisit(\App\Models\Opd $opd, string $token, string $name): string
    {
        $visitId = $this->insertVisit($opd->id, '2026-10-15 11:00:00', ['nama_tamu' => $name]);
        $this->updateDevice($token, ['kunjungan_menunggu_id' => $visitId]);

        return $visitId;
    }

    public function test_it_stores_the_visit_under_the_opd_of_the_display_token_even_if_the_request_names_another_opd(): void
    {
        $this->showFormOn('token-a', $this->adminA->id);
        $visitsOfBBefore = DB::table('kunjungan')->where('opd_id', $this->opdB->id)->count();

        $this->submitAs('token-a', ['opd_id' => $this->opdB->id])->assertRedirect(route('display.confirmation'));

        $stored = DB::table('kunjungan')->where('nama_tamu', 'Tamu Lintas OPD')->get();
        $this->assertCount(1, $stored);
        $this->assertSame($this->opdA->id, $stored[0]->opd_id);
        $this->assertSame($this->adminA->id, $stored[0]->dicatat_oleh);
        $this->assertSame($visitsOfBBefore, DB::table('kunjungan')->where('opd_id', $this->opdB->id)->count());
    }

    public function test_it_rejects_a_bidang_of_another_opd_and_stores_nothing(): void
    {
        $this->showFormOn('token-a', $this->adminA->id);

        $this->submitAs('token-a', ['bidang_id' => $this->bidangIdOf($this->opdB, 'PKP')])
            ->assertSessionHasErrors('bidang_id');

        $this->assertDatabaseMissing('kunjungan', ['nama_tamu' => 'Tamu Lintas OPD']);
        $this->assertDatabaseHas('display_devices', ['id' => $this->deviceId('token-a'), 'tampilkan_form' => true]);
    }

    public function test_it_accepts_a_bidang_of_its_own_opd(): void
    {
        $this->showFormOn('token-b', $this->adminB->id);
        $bidangId = $this->bidangIdOf($this->opdB, 'SDP');

        $this->submitAs('token-b', ['bidang_id' => $bidangId])->assertRedirect(route('display.confirmation'));

        $this->assertDatabaseHas('kunjungan', [
            'nama_tamu' => 'Tamu Lintas OPD',
            'opd_id' => $this->opdB->id,
            'bidang_id' => $bidangId,
        ]);
    }

    public function test_it_rejects_an_inactive_bidang_of_its_own_opd(): void
    {
        $inactive = $this->insertBidang($this->opdA->id, 'NONAKTIF', false);
        $this->showFormOn('token-a', $this->adminA->id);

        $this->submitAs('token-a', ['bidang_id' => $inactive])->assertSessionHasErrors('bidang_id');

        $this->assertDatabaseMissing('kunjungan', ['nama_tamu' => 'Tamu Lintas OPD']);
    }

    public function test_the_status_endpoint_reports_only_the_device_of_the_token(): void
    {
        $this->updateDevice('token-a', ['tampilkan_form' => true]);
        $this->updateDevice('token-b', ['tampilkan_form' => false]);
        $waitingOfB = $this->makeWaitingVisit($this->opdB, 'token-b', 'Tamu Menunggu B');

        $this->withCookie(DisplayDevice::COOKIE, 'token-a')->get(route('display.status'))
            ->assertOk()
            ->assertJson(['tampilkan_form' => true, 'waiting_key' => null]);

        $keyOfB = $this->withCookie(DisplayDevice::COOKIE, 'token-b')->get(route('display.status'))
            ->assertOk()
            ->assertJson(['tampilkan_form' => false])
            ->json('waiting_key');

        $this->assertStringStartsWith($waitingOfB.'@', $keyOfB);
    }

    public function test_polling_updates_only_the_polling_device(): void
    {
        $stale = '2026-01-01 00:00:00';
        $this->updateDevice('token-a', ['terakhir_aktif' => $stale]);
        $this->updateDevice('token-a-2', ['terakhir_aktif' => $stale]);
        $this->updateDevice('token-b', ['terakhir_aktif' => $stale]);

        $this->withCookie(DisplayDevice::COOKIE, 'token-a')->get(route('display.status'))->assertOk();

        $this->assertNotSame($stale, (string) DB::table('display_devices')->where('id', $this->deviceId('token-a'))->value('terakhir_aktif'));
        $this->assertSame($stale, (string) DB::table('display_devices')->where('id', $this->deviceId('token-a-2'))->value('terakhir_aktif'));
        $this->assertSame($stale, (string) DB::table('display_devices')->where('id', $this->deviceId('token-b'))->value('terakhir_aktif'));
    }

    public function test_a_posted_visit_id_of_another_opd_does_not_receive_the_signature(): void
    {
        $waitingOfA = $this->makeWaitingVisit($this->opdA, 'token-a', 'Tamu Menunggu A');
        $waitingOfB = $this->makeWaitingVisit($this->opdB, 'token-b', 'Tamu Menunggu B');

        $this->withCookie(DisplayDevice::COOKIE, 'token-a')
            ->post(route('display.signature.store'), ['tanda_tangan' => self::PNG, 'kunjungan_id' => $waitingOfB])
            ->assertRedirect(route('display.confirmation'));

        $this->assertNotNull(DB::table('kunjungan')->where('id', $waitingOfA)->value('tanda_tangan'));
        $this->assertNull(DB::table('kunjungan')->where('id', $waitingOfB)->value('tanda_tangan'));
        $this->assertSame($waitingOfB, DB::table('display_devices')->where('id', $this->deviceId('token-b'))->value('kunjungan_menunggu_id'));
    }

    public function test_a_token_with_nothing_waiting_cannot_sign_a_visit_of_another_opd(): void
    {
        $waitingOfB = $this->makeWaitingVisit($this->opdB, 'token-b', 'Tamu Menunggu B');

        $this->withCookie(DisplayDevice::COOKIE, 'token-a')
            ->post(route('display.signature.store'), ['tanda_tangan' => self::PNG, 'kunjungan_id' => $waitingOfB])
            ->assertRedirect(route('display.show'));

        $this->assertNull(DB::table('kunjungan')->where('id', $waitingOfB)->value('tanda_tangan'));
        $this->assertSame($waitingOfB, DB::table('display_devices')->where('id', $this->deviceId('token-b'))->value('kunjungan_menunggu_id'));
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_display_page_lists_only_the_bidang_and_opd_of_the_token(): void
    {
        $this->withoutVite();
        $this->insertBidang($this->opdA->id, 'NONAKTIF', false);
        $expected = DB::table('bidang')->where(['opd_id' => $this->opdA->id, 'aktif' => true])->orderBy('id')->pluck('id')->all();

        $this->withCookie(DisplayDevice::COOKIE, 'token-a')->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('opd', fn ($opd) => $opd->id === $this->opdA->id)
            ->assertViewHas('bidang', fn ($bidang) => $bidang->pluck('id')->sort()->values()->all() === $expected);
    }
}
