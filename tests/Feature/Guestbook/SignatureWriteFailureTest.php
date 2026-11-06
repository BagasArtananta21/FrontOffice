<?php

namespace Tests\Feature\Guestbook;

use App\Exceptions\SignatureStorageException;
use App\Models\DisplayDevice;
use App\Services\KunjunganService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class SignatureWriteFailureTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo('2026-10-15 09:30:00');
    }

    private function failDiskWrites(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->andReturn(true);

        Storage::set('local', $disk);
    }

    public function test_a_failed_write_keeps_the_signature_request_waiting(): void
    {
        $visitId = $this->makeWaitingVisit();
        $this->failDiskWrites();

        $this->submitSignature()->assertSessionHasErrors('form');

        $this->assertNull($this->rawRow('kunjungan', $visitId)['tanda_tangan']);
        $this->assertSame($visitId, $this->deviceRow()['kunjungan_menunggu_id']);
    }

    public function test_a_failed_write_creates_no_visit_and_keeps_the_form_open(): void
    {
        $this->showForm();
        $this->failDiskWrites();

        $this->submitVisit()->assertSessionHasErrors('form');

        $this->assertSame(0, DB::table('kunjungan')->count());
        $this->assertSame(1, (int) $this->deviceRow()['tampilkan_form']);
    }

    public function test_an_undecodable_signature_is_refused_instead_of_stored_as_an_empty_file(): void
    {
        $visitId = $this->makeWaitingVisit();
        app()->instance('current_opd_id', $this->opd->id);
        $device = DisplayDevice::findOrFail($this->deviceId);

        try {
            app(KunjunganService::class)->recordSignature($device, 'data:image/png;base64,@@@');
            $this->fail('Seharusnya melempar SignatureStorageException.');
        } catch (SignatureStorageException) {
            $this->assertNull($this->rawRow('kunjungan', $visitId)['tanda_tangan']);
            $this->assertSame($visitId, $this->deviceRow()['kunjungan_menunggu_id']);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }
}
