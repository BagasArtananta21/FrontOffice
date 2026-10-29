<?php

namespace Tests\Feature\Guestbook;

use App\Models\DisplayDevice;
use App\Models\Kunjungan;
use App\Services\KunjunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class SignatureLifecycleTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    private const NOW = '2026-10-15 10:00:00';

    private string $visitId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo(self::NOW);
        $this->actingAs($this->officer);
        $this->visitId = $this->makeWaitingVisit(['updated_at' => $this->secondsAgo(60)]);
    }

    private function service(): KunjunganService
    {
        return app(KunjunganService::class);
    }

    private function secondsAgo(int $seconds): string
    {
        return now()->subSeconds($seconds)->format('Y-m-d H:i:s');
    }

    private function device(): DisplayDevice
    {
        return DisplayDevice::findOrFail($this->deviceId);
    }

    private function reasonOfVisit(): ?string
    {
        return $this->rawRow('kunjungan', $this->visitId)['alasan_tanpa_ttd'];
    }

    private function makeExpired(int $secondsOld = 301): void
    {
        DB::table('kunjungan')->where('id', $this->visitId)->update(['updated_at' => $this->secondsAgo($secondsOld)]);
    }

    private function disconnect(): void
    {
        $this->updateDevice(['terakhir_aktif' => $this->secondsAgo(31)]);
    }

    public function test_cancel_releases_the_display_once_and_the_second_call_returns_false(): void
    {
        $this->assertTrue($this->service()->cancelSignatureRequest());
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertFalse($this->service()->cancelSignatureRequest(Kunjungan::REASON_GUEST_REFUSED));

        $this->assertSame(Kunjungan::REASON_GUEST_LEFT, $this->reasonOfVisit());
    }

    public static function chosenReasons(): array
    {
        return [
            'guest left' => [Kunjungan::REASON_GUEST_LEFT],
            'guest refused' => [Kunjungan::REASON_GUEST_REFUSED],
        ];
    }

    #[DataProvider('chosenReasons')]
    public function test_cancel_stores_the_reason_the_officer_chose_while_the_display_is_connected(string $reason): void
    {
        $this->assertTrue($this->service()->cancelSignatureRequest($reason));

        $this->assertSame($reason, $this->reasonOfVisit());
    }

    public function test_cancel_without_a_reason_defaults_to_guest_left(): void
    {
        $this->service()->cancelSignatureRequest();

        $this->assertSame('tamu_sudah_pergi', $this->reasonOfVisit());
    }

    #[DataProvider('chosenReasons')]
    public function test_cancel_forces_display_unavailable_when_the_display_is_disconnected(string $reason): void
    {
        $this->disconnect();

        $this->assertTrue($this->service()->cancelSignatureRequest($reason));

        $this->assertSame('display_tidak_tersedia', $this->reasonOfVisit());
    }

    public function test_cancel_treats_a_display_at_exactly_30_seconds_as_connected(): void
    {
        $this->updateDevice(['terakhir_aktif' => $this->secondsAgo(30)]);

        $this->service()->cancelSignatureRequest(Kunjungan::REASON_GUEST_REFUSED);

        $this->assertSame('tamu_menolak', $this->reasonOfVisit());
    }

    public function test_cancel_keeps_the_visit_recorded_without_a_signature(): void
    {
        $this->service()->cancelSignatureRequest(Kunjungan::REASON_GUEST_REFUSED);

        $row = $this->rawRow('kunjungan', $this->visitId);
        $this->assertNull($row['tanda_tangan']);
        $this->assertSame('Tamu Menunggu', $row['nama_tamu']);
        $this->assertNotEmpty($row['nomor_kunjungan']);
        $this->assertTrue(DisplayDevice::availableForSignature()->whereKey($this->deviceId)->exists());
    }

    public static function reasonsNotAllowedForCancel(): array
    {
        return [
            'timeout' => [Kunjungan::REASON_TIMEOUT],
            'display unavailable' => [Kunjungan::REASON_DISPLAY_UNAVAILABLE],
            'free text' => ['bosan menunggu'],
            'empty' => [''],
        ];
    }

    #[DataProvider('reasonsNotAllowedForCancel')]
    public function test_cancel_refuses_a_reason_it_cannot_choose_and_leaves_the_request_waiting(string $reason): void
    {
        try {
            $this->service()->cancelSignatureRequest($reason);
            $this->fail('Seharusnya melempar InvalidArgumentException.');
        } catch (InvalidArgumentException) {
            $this->assertSame($this->visitId, $this->deviceRow()['kunjungan_menunggu_id']);
            $this->assertNull($this->reasonOfVisit());
        }
    }

    public function test_cancel_returns_false_when_nothing_is_waiting(): void
    {
        $this->updateDevice(['kunjungan_menunggu_id' => null]);

        $this->assertFalse($this->service()->cancelSignatureRequest());
        $this->assertNull($this->reasonOfVisit());
    }

    public function test_cancel_only_touches_the_display_of_the_officers_opd(): void
    {
        $this->updateDevice(['kunjungan_menunggu_id' => null]);
        $other = $this->makeOpd('LAIN');
        $otherDevice = $this->insertDisplay($other->id, 'token-lain');
        $otherVisit = $this->insertVisit($other->id, self::NOW, ['updated_at' => $this->secondsAgo(10)]);
        DB::table('display_devices')->where('id', $otherDevice)->update(['kunjungan_menunggu_id' => $otherVisit]);

        $this->assertFalse($this->service()->cancelSignatureRequest(Kunjungan::REASON_GUEST_REFUSED));

        $this->assertSame($otherVisit, DB::table('display_devices')->where('id', $otherDevice)->value('kunjungan_menunggu_id'));
        $this->assertNull(DB::table('kunjungan')->where('id', $otherVisit)->value('alasan_tanpa_ttd'));
    }

    public static function requestAges(): array
    {
        return [
            'fresh' => [0, false],
            '4 minutes 59 seconds' => [299, false],
            'exactly 5 minutes' => [300, false],
            '5 minutes 1 second' => [301, true],
            '1 hour' => [3600, true],
        ];
    }

    #[DataProvider('requestAges')]
    public function test_expire_acts_only_after_5_minutes(int $age, bool $expected): void
    {
        $this->makeExpired($age);

        $this->assertSame($expected, $this->service()->expireSignatureRequest($this->device()));
        $this->assertSame($expected ? null : $this->visitId, $this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertSame($expected ? 'waktu_habis' : null, $this->reasonOfVisit());
    }

    public function test_expire_records_timeout_when_the_display_is_connected(): void
    {
        $this->makeExpired();

        $this->assertTrue($this->service()->expireSignatureRequest($this->device()));

        $this->assertSame('waktu_habis', $this->reasonOfVisit());
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_expire_records_display_unavailable_when_the_display_is_disconnected(): void
    {
        $this->makeExpired();
        $this->disconnect();

        $this->assertTrue($this->service()->expireSignatureRequest($this->device()));

        $this->assertSame('display_tidak_tersedia', $this->reasonOfVisit());
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_expire_is_idempotent(): void
    {
        $this->makeExpired();
        $device = $this->device();

        $this->assertTrue($this->service()->expireSignatureRequest($device));
        $this->disconnect();
        $this->assertFalse($this->service()->expireSignatureRequest($device));

        $this->assertSame('waktu_habis', $this->reasonOfVisit());
    }

    public function test_expire_with_a_stale_device_model_does_not_overwrite_a_manual_cancel(): void
    {
        $this->makeExpired();
        $stale = $this->device();

        $this->assertTrue($this->service()->cancelSignatureRequest(Kunjungan::REASON_GUEST_REFUSED));
        $this->assertFalse($this->service()->expireSignatureRequest($stale));

        $this->assertSame('tamu_menolak', $this->reasonOfVisit());
    }

    public function test_cancel_after_expiry_returns_false_and_keeps_the_timeout_reason(): void
    {
        $this->makeExpired();
        $this->service()->expireSignatureRequest($this->device());

        $this->assertFalse($this->service()->cancelSignatureRequest(Kunjungan::REASON_GUEST_REFUSED));

        $this->assertSame('waktu_habis', $this->reasonOfVisit());
    }

    public function test_expire_returns_false_when_nothing_is_waiting(): void
    {
        $this->updateDevice(['kunjungan_menunggu_id' => null]);

        $this->assertFalse($this->service()->expireSignatureRequest($this->device()));
    }

    public function test_expire_keeps_the_visit_recorded_and_frees_the_display(): void
    {
        $this->makeExpired();

        $this->service()->expireSignatureRequest($this->device());

        $row = $this->rawRow('kunjungan', $this->visitId);
        $this->assertNull($row['tanda_tangan']);
        $this->assertNotEmpty($row['nomor_kunjungan']);
        $this->assertTrue(DisplayDevice::availableForSignature()->whereKey($this->deviceId)->exists());
    }

    public function test_an_edit_by_the_officer_restarts_the_five_minutes(): void
    {
        $this->makeExpired(290);
        $this->travel(20)->seconds();
        $this->assertTrue($this->device()->signatureRequestExpired());

        $this->device()->kunjunganMenunggu->forceFill(['nama_tamu' => 'Nama Dibetulkan'])->save();

        $this->assertFalse($this->device()->signatureRequestExpired());
        $this->assertFalse($this->service()->expireSignatureRequest($this->device()));
        $this->assertSame($this->visitId, $this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_a_late_signature_after_expiry_is_refused(): void
    {
        $this->makeExpired();
        $this->service()->expireSignatureRequest($this->device());

        $this->submitSignature()->assertRedirect(route('display.show'));

        $this->assertNull($this->rawRow('kunjungan', $this->visitId)['tanda_tangan']);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
