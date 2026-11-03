<?php

namespace Tests\Feature\Guestbook;

use App\Services\KunjunganService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class SignatureFileCleanupTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->travelTo('2026-10-15 09:30:00');
        app()->instance('current_opd_id', $this->opd->id);
    }

    public function test_no_signature_file_is_left_when_the_visit_cannot_be_stored(): void
    {
        try {
            app(KunjunganService::class)->recordFromDisplay(
                $this->visitPayload(['bidang_id' => (string) Str::uuid()]),
                $this->officer->id,
            );

            $this->fail('Seharusnya melempar QueryException karena bidang_id tidak ada.');
        } catch (QueryException) {
            $this->assertDatabaseCount('kunjungan', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_the_signature_file_is_written_when_the_visit_is_stored(): void
    {
        $visit = app(KunjunganService::class)->recordFromDisplay($this->visitPayload(), $this->officer->id);

        $this->assertSame([$visit->tanda_tangan], Storage::disk('local')->allFiles());
    }
}
