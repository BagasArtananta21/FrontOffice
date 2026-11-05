<?php

namespace Tests\Feature\Guestbook;

use App\Exceptions\DisplayUnavailableException;
use App\Models\DisplayDevice;
use App\Models\Kunjungan;
use App\Services\KunjunganService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class SignatureReRequestTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    private const NOW = '2026-10-15 09:30:00';

    private const TWO_HOURS_AGO = '2026-10-15 07:30:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo(self::NOW);
        $this->actingAs($this->officer);
    }

    private function service(): KunjunganService
    {
        return app(KunjunganService::class);
    }

    private function visit(string $id): Kunjungan
    {
        return Kunjungan::findOrFail($id);
    }

    /**
     * A visit from two hours ago that was released without a signature.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function oldVisit(array $overrides = []): string
    {
        return $this->insertVisit($this->opd->id, self::TWO_HOURS_AGO, [
            'alasan_tanpa_ttd' => Kunjungan::REASON_DISPLAY_UNAVAILABLE,
            'created_at' => self::TWO_HOURS_AGO,
            'updated_at' => self::TWO_HOURS_AGO,
            ...$overrides,
        ]);
    }

    public function test_it_points_the_display_at_the_existing_visit_and_clears_the_reason(): void
    {
        $id = $this->oldVisit();

        $this->service()->requestSignatureFor($this->visit($id));

        $row = $this->rawRow('kunjungan', $id);
        $this->assertSame($id, $this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertNull($row['alasan_tanpa_ttd']);
        $this->assertSame(self::TWO_HOURS_AGO, $row['waktu_datang']);
        $this->assertSame('20261015-0001', $row['nomor_kunjungan']);
        $this->assertSame(1, DB::table('kunjungan')->count());
    }

    public function test_it_restarts_the_five_minute_window(): void
    {
        $id = $this->oldVisit();

        $this->service()->requestSignatureFor($this->visit($id));

        $this->assertSame(self::NOW, $this->rawRow('kunjungan', $id)['updated_at']);
        $this->assertFalse(DisplayDevice::findOrFail($this->deviceId)->signatureRequestExpired());
    }

    public function test_it_restarts_the_window_even_when_the_reason_was_already_empty(): void
    {
        $id = $this->oldVisit(['alasan_tanpa_ttd' => null]);

        $this->service()->requestSignatureFor($this->visit($id));

        $this->assertSame(self::NOW, $this->rawRow('kunjungan', $id)['updated_at']);
        $this->assertFalse(DisplayDevice::findOrFail($this->deviceId)->signatureRequestExpired());
    }

    public function test_a_visit_from_the_very_start_of_today_is_still_eligible(): void
    {
        $id = $this->oldVisit(['waktu_datang' => '2026-10-15 00:00:00']);

        $this->service()->requestSignatureFor($this->visit($id));

        $this->assertSame($id, $this->deviceRow()['kunjungan_menunggu_id']);
    }

    public static function ineligibleVisits(): array
    {
        return [
            'arrived one second before today' => [['waktu_datang' => '2026-10-14 23:59:59']],
            'already signed' => [['tanda_tangan' => 'tanda-tangan/2026/10/sudah.png', 'alasan_tanpa_ttd' => null]],
        ];
    }

    #[DataProvider('ineligibleVisits')]
    public function test_it_refuses_an_ineligible_visit_and_changes_nothing(array $overrides): void
    {
        $id = $this->oldVisit($overrides);
        $before = $this->rawRow('kunjungan', $id);

        try {
            $this->service()->requestSignatureFor($this->visit($id));
            $this->fail('Seharusnya melempar InvalidArgumentException.');
        } catch (InvalidArgumentException) {
            $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
            $this->assertSame($before, $this->rawRow('kunjungan', $id));
        }
    }

    public static function unavailableStates(): array
    {
        return [
            'display not connected' => [['terakhir_aktif' => '2026-10-15 09:29:00']],
            'display never reported' => [['terakhir_aktif' => null]],
            'guest form is open' => [['tampilkan_form' => true]],
            'display deactivated' => [['aktif' => false]],
        ];
    }

    #[DataProvider('unavailableStates')]
    public function test_it_refuses_when_the_display_cannot_take_a_request_and_changes_nothing(array $deviceState): void
    {
        $id = $this->oldVisit();
        $before = $this->rawRow('kunjungan', $id);
        $this->updateDevice($deviceState);

        try {
            $this->service()->requestSignatureFor($this->visit($id));
            $this->fail('Seharusnya melempar DisplayUnavailableException.');
        } catch (DisplayUnavailableException) {
            $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
            $this->assertSame($before, $this->rawRow('kunjungan', $id));
        }
    }

    public function test_it_refuses_a_visit_that_is_already_waiting_because_the_display_is_busy(): void
    {
        $id = $this->makeWaitingVisit();

        $this->expectException(DisplayUnavailableException::class);

        $this->service()->requestSignatureFor($this->visit($id));
    }

    public function test_can_request_signature_is_true_for_an_unsigned_visit_from_today(): void
    {
        $this->assertTrue($this->visit($this->oldVisit())->canRequestSignature());
    }

    public function test_can_request_signature_is_false_while_the_visit_is_waiting(): void
    {
        $id = $this->makeWaitingVisit();

        $this->assertFalse($this->visit($id)->canRequestSignature());
    }

    public function test_it_never_touches_a_visit_of_another_opd(): void
    {
        $other = $this->makeOpd('LAIN');
        $foreignId = $this->insertVisit($other->id, self::TWO_HOURS_AGO, [
            'alasan_tanpa_ttd' => Kunjungan::REASON_GUEST_LEFT,
            'updated_at' => self::TWO_HOURS_AGO,
        ]);
        $foreign = Kunjungan::withoutGlobalScope('opd')->findOrFail($foreignId);

        try {
            $this->service()->requestSignatureFor($foreign);
            $this->fail('Seharusnya melempar ModelNotFoundException.');
        } catch (ModelNotFoundException) {
            $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
            $this->assertSame(Kunjungan::REASON_GUEST_LEFT, $this->rawRow('kunjungan', $foreignId)['alasan_tanpa_ttd']);
        }
    }

    public function test_the_signature_can_be_recorded_after_the_new_request(): void
    {
        $id = $this->oldVisit();
        $this->service()->requestSignatureFor($this->visit($id));

        $this->submitSignature()->assertRedirect(route('display.confirmation'));

        $row = $this->rawRow('kunjungan', $id);
        $this->assertNotNull($row['tanda_tangan']);
        $this->assertNull($row['alasan_tanpa_ttd']);
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertSame(1, DB::table('kunjungan')->count());
        Storage::disk('local')->assertExists($row['tanda_tangan']);
    }

    public function test_the_request_expires_five_minutes_after_the_new_request_not_after_arrival(): void
    {
        $id = $this->oldVisit();
        $this->service()->requestSignatureFor($this->visit($id));

        $this->travelTo(now()->addMinutes(4));
        $this->updateDevice(['terakhir_aktif' => now()]);
        $this->assertFalse($this->service()->expireSignatureRequest(DisplayDevice::findOrFail($this->deviceId)));

        $this->travelTo(now()->addMinutes(2));
        $this->updateDevice(['terakhir_aktif' => now()]);
        $this->assertTrue($this->service()->expireSignatureRequest(DisplayDevice::findOrFail($this->deviceId)));

        $this->assertSame(Kunjungan::REASON_TIMEOUT, $this->rawRow('kunjungan', $id)['alasan_tanpa_ttd']);
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
    }
}
