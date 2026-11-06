<?php

namespace Tests\Feature\Guestbook;

use App\Filament\Admin\Resources\Kunjungans\Pages\ManageKunjungans;
use App\Models\Kunjungan;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

/**
 * The "Minta Tanda Tangan" row action on Tamu Hari Ini. The rules themselves
 * are covered by SignatureReRequestTest; this checks the panel wiring.
 */
class RequestSignatureActionTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    private const NOW = '2026-10-15 09:30:00';

    private const EARLIER = '2026-10-15 08:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo(self::NOW);
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->officer);
    }

    /**
     * A visit from earlier today that was released without a signature.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function releasedVisit(array $overrides = []): Kunjungan
    {
        $id = $this->insertVisit($this->opd->id, self::EARLIER, [
            'alasan_tanpa_ttd' => Kunjungan::REASON_DISPLAY_UNAVAILABLE,
            'created_at' => self::EARLIER,
            'updated_at' => self::EARLIER,
            ...$overrides,
        ]);

        return Kunjungan::findOrFail($id);
    }

    private function rowAction(Kunjungan $visit): TestAction
    {
        return TestAction::make('requestSignature')->table($visit);
    }

    /**
     * @return \Closure(string, array<int|string, mixed>): bool
     */
    private function swalWith(string $icon, string $title): \Closure
    {
        return function (string $event, array $params) use ($icon, $title): bool {
            $payload = $params[0] ?? $params;

            return ($payload['icon'] ?? null) === $icon && ($payload['title'] ?? null) === $title;
        };
    }

    public function test_the_action_is_visible_for_an_unsigned_visit_that_is_not_waiting(): void
    {
        $visit = $this->releasedVisit();

        Livewire::test(ManageKunjungans::class)->assertActionVisible($this->rowAction($visit));
    }

    public function test_the_action_is_hidden_for_a_visit_that_is_already_signed(): void
    {
        $visit = $this->releasedVisit([
            'tanda_tangan' => 'tanda-tangan/2026/10/sudah.png',
            'alasan_tanpa_ttd' => null,
        ]);

        Livewire::test(ManageKunjungans::class)->assertActionHidden($this->rowAction($visit));
    }

    public function test_the_action_is_hidden_for_the_visit_that_is_waiting_on_the_display(): void
    {
        $visit = Kunjungan::findOrFail($this->makeWaitingVisit());

        Livewire::test(ManageKunjungans::class)->assertActionHidden($this->rowAction($visit));
    }

    public function test_calling_the_action_sends_the_visit_to_the_display(): void
    {
        $visit = $this->releasedVisit();

        Livewire::test(ManageKunjungans::class)
            ->callAction($this->rowAction($visit))
            ->assertDispatched('swal', $this->swalWith('success', 'Dikirim ke Display'));

        $this->assertSame($visit->id, $this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertNull($this->rawRow('kunjungan', $visit->id)['alasan_tanpa_ttd']);
        $this->assertSame(self::NOW, $this->rawRow('kunjungan', $visit->id)['updated_at']);
    }

    public function test_it_warns_and_changes_nothing_when_the_display_is_not_connected(): void
    {
        $visit = $this->releasedVisit();
        $this->updateDevice(['terakhir_aktif' => now()->subSeconds(120)->format('Y-m-d H:i:s')]);

        Livewire::test(ManageKunjungans::class)
            ->callAction($this->rowAction($visit))
            ->assertDispatched('swal', $this->swalWith('warning', 'Display Tidak Tersedia'))
            ->assertNotDispatched('swal', $this->swalWith('success', 'Dikirim ke Display'));

        $this->assertNull($this->deviceRow()['kunjungan_menunggu_id']);
        $this->assertSame(
            Kunjungan::REASON_DISPLAY_UNAVAILABLE,
            $this->rawRow('kunjungan', $visit->id)['alasan_tanpa_ttd'],
        );
        $this->assertSame(self::EARLIER, $this->rawRow('kunjungan', $visit->id)['updated_at']);
    }

    public function test_it_warns_when_the_display_is_busy_with_another_guest(): void
    {
        $visit = $this->releasedVisit();
        $waitingId = $this->makeWaitingVisit();

        Livewire::test(ManageKunjungans::class)
            ->callAction($this->rowAction($visit))
            ->assertDispatched('swal', $this->swalWith('warning', 'Display Tidak Tersedia'));

        $this->assertSame($waitingId, $this->deviceRow()['kunjungan_menunggu_id']);
    }
}
