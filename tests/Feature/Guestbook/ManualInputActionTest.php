<?php

namespace Tests\Feature\Guestbook;

use App\Filament\Admin\Resources\Kunjungans\Pages\ManageKunjungans;
use App\Models\Opd;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

/**
 * The manual-input rules that live only in the panel form (the service does not repeat them).
 */
class ManualInputActionTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    private const NOW = '2026-10-15 09:30:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo(self::NOW);
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->officer);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formData(array $overrides = []): array
    {
        return [
            'nama_tamu' => 'Ni Made Ayu',
            'jenis_kelamin' => 'perempuan',
            'waktu_datang' => '2026-10-15 09:00:00',
            'keperluan' => 'Mengurus surat keterangan',
            'signature_method' => 'none',
            'alasan_tanpa_ttd' => 'tamu_menolak',
            ...$overrides,
        ];
    }

    private function submit(array $overrides = [])
    {
        return Livewire::test(ManageKunjungans::class)->callAction('manualInput', data: $this->formData($overrides));
    }

    private function disconnectDisplay(): void
    {
        $this->updateDevice(['terakhir_aktif' => now()->subSeconds(120)->format('Y-m-d H:i:s')]);
    }

    private function visitCount(): int
    {
        return DB::table('kunjungan')->count();
    }

    public static function requiredFields(): array
    {
        return [
            'name' => ['nama_tamu'],
            'gender' => ['jenis_kelamin'],
            'arrival time' => ['waktu_datang'],
            'purpose' => ['keperluan'],
            'signature method' => ['signature_method'],
        ];
    }

    #[DataProvider('requiredFields')]
    public function test_it_requires_the_four_mandatory_fields_and_the_signature_choice(string $field): void
    {
        $this->submit([$field => null])->assertHasActionErrors([$field => 'required']);

        $this->assertSame(0, $this->visitCount());
    }

    public function test_it_records_a_visit_without_signature_when_a_reason_is_given(): void
    {
        $this->submit(['alasan_tanpa_ttd' => 'tamu_sudah_pergi'])->assertHasNoActionErrors();

        $this->assertDatabaseHas('kunjungan', [
            'nama_tamu' => 'Ni Made Ayu',
            'opd_id' => $this->opd->id,
            'alasan_tanpa_ttd' => 'tamu_sudah_pergi',
            'tanda_tangan' => null,
            'sumber_input' => 'frontoffice',
            'dicatat_oleh' => $this->officer->id,
            'waktu_datang' => '2026-10-15 09:00:00',
        ]);
    }

    public function test_it_requires_a_reason_when_no_signature_is_taken(): void
    {
        $this->submit(['alasan_tanpa_ttd' => null])->assertHasActionErrors(['alasan_tanpa_ttd' => 'required']);

        $this->assertSame(0, $this->visitCount());
    }

    public function test_display_unavailable_is_refused_as_a_reason_while_the_display_is_connected(): void
    {
        $this->submit(['alasan_tanpa_ttd' => 'display_tidak_tersedia'])->assertHasActionErrors(['alasan_tanpa_ttd']);

        $this->assertSame(0, $this->visitCount());
    }

    public function test_display_unavailable_is_accepted_when_the_display_is_disconnected(): void
    {
        $this->disconnectDisplay();

        $this->submit(['alasan_tanpa_ttd' => 'display_tidak_tersedia'])->assertHasNoActionErrors();

        $this->assertDatabaseHas('kunjungan', ['nama_tamu' => 'Ni Made Ayu', 'alasan_tanpa_ttd' => 'display_tidak_tersedia']);
    }

    public static function reasonsAFrontOfficerMayNotPick(): array
    {
        return [
            'timeout is system-only' => ['waktu_habis'],
            'free text' => ['malas'],
        ];
    }

    #[DataProvider('reasonsAFrontOfficerMayNotPick')]
    public function test_it_refuses_reasons_that_are_not_offered(string $reason): void
    {
        $this->disconnectDisplay();

        $this->submit(['alasan_tanpa_ttd' => $reason])->assertHasActionErrors(['alasan_tanpa_ttd']);

        $this->assertSame(0, $this->visitCount());
    }

    public function test_it_sends_the_visit_to_the_display_when_it_is_available(): void
    {
        $this->submit(['signature_method' => 'display', 'alasan_tanpa_ttd' => null])->assertHasNoActionErrors();

        $visitId = DB::table('kunjungan')->value('id');
        $this->assertNotNull($visitId);
        $this->assertSame($visitId, $this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertNull($this->rawRow('kunjungan', $visitId)['alasan_tanpa_ttd']);
    }

    public function test_it_does_not_accept_the_display_choice_while_the_display_is_disconnected(): void
    {
        $this->disconnectDisplay();

        $this->submit(['signature_method' => 'display', 'alasan_tanpa_ttd' => null]);

        $this->assertSame(0, $this->visitCount());
        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_it_does_not_accept_the_display_choice_while_the_display_is_busy(): void
    {
        $this->makeWaitingVisit();
        $before = $this->visitCount();

        $this->submit(['signature_method' => 'display', 'alasan_tanpa_ttd' => null]);

        $this->assertSame($before, $this->visitCount());
    }

    public function test_it_normalizes_the_phone_number(): void
    {
        $this->submit(['no_hp' => '+62 812-3456-7890'])->assertHasNoActionErrors();

        $this->assertDatabaseHas('kunjungan', ['nama_tamu' => 'Ni Made Ayu', 'no_hp' => '081234567890']);
    }

    public function test_it_rejects_a_phone_number_with_letters(): void
    {
        $this->submit(['no_hp' => '0812abc4567'])->assertHasActionErrors(['no_hp' => 'regex']);

        $this->assertSame(0, $this->visitCount());
    }

    public function test_it_refuses_an_arrival_time_in_the_future(): void
    {
        $this->submit(['waktu_datang' => '2026-10-15 11:00:00'])->assertHasActionErrors(['waktu_datang']);

        $this->assertSame(0, $this->visitCount());
    }

    public function test_it_refuses_a_bidang_of_another_opd(): void
    {
        $foreign = $this->insertBidang($this->makeOpd('LAIN')->id, 'SDP');

        $this->submit(['bidang_id' => $foreign])->assertHasActionErrors(['bidang_id']);

        $this->assertSame(0, $this->visitCount());
    }

    public function test_it_accepts_a_bidang_of_its_own_opd(): void
    {
        $own = $this->insertBidang($this->opd->id, 'PKP');

        $this->submit(['bidang_id' => $own])->assertHasNoActionErrors();

        $this->assertDatabaseHas('kunjungan', ['nama_tamu' => 'Ni Made Ayu', 'bidang_id' => $own]);
    }
}
