<?php

namespace Tests\Feature\Guestbook;

use App\Filament\Admin\Resources\Kunjungans\Pages\ManageKunjungans;
use App\Models\Kunjungan;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

/**
 * The Ubah action reuses the manual-input form, so the bidang rule must not lock out a visit
 * whose bidang was deactivated after the visit was recorded.
 */
class EditVisitBidangTest extends TestCase
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

    private function todaysVisit(string $bidangId): Kunjungan
    {
        $id = $this->insertVisit($this->opd->id, '2026-10-15 09:00:00', [
            'bidang_id' => $bidangId,
            'dicatat_oleh' => $this->officer->id,
        ]);

        return Kunjungan::query()->findOrFail($id);
    }

    private function edit(Kunjungan $visit, array $data)
    {
        return Livewire::test(ManageKunjungans::class)
            ->callAction(TestAction::make('edit')->table($visit), data: $data);
    }

    public function test_it_keeps_saving_a_visit_whose_bidang_was_deactivated_afterwards(): void
    {
        $bidang = $this->insertBidang($this->opd->id, 'PKP');
        $visit = $this->todaysVisit($bidang);
        DB::table('bidang')->where('id', $bidang)->update(['aktif' => false]);

        $this->edit($visit, ['nama_tamu' => 'Nama Baru'])->assertHasNoActionErrors();

        $row = $this->rawRow('kunjungan', $visit->id);
        $this->assertSame('Nama Baru', $row['nama_tamu']);
        $this->assertSame($bidang, $row['bidang_id']);
    }

    public function test_it_refuses_switching_to_an_inactive_bidang(): void
    {
        $visit = $this->todaysVisit($this->insertBidang($this->opd->id, 'PKP'));
        $inactive = $this->insertBidang($this->opd->id, 'OFF', false);

        $this->edit($visit, ['bidang_id' => $inactive])->assertHasActionErrors(['bidang_id']);

        $this->assertNotSame($inactive, $this->rawRow('kunjungan', $visit->id)['bidang_id']);
    }

    public function test_it_still_allows_switching_to_another_active_bidang(): void
    {
        $visit = $this->todaysVisit($this->insertBidang($this->opd->id, 'PKP'));
        $other = $this->insertBidang($this->opd->id, 'SDP');

        $this->edit($visit, ['bidang_id' => $other])->assertHasNoActionErrors();

        $this->assertSame($other, $this->rawRow('kunjungan', $visit->id)['bidang_id']);
    }
}
