<?php

namespace Tests\Feature\Trigger;

use App\Models\NomorSurat;
use App\Models\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\AssertsTriggerErrors;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class LetterDeleteTriggerTest extends TestCase
{
    use AssertsTriggerErrors, BuildsFixtures, RefreshDatabase;

    private const MESSAGE = 'Nomor surat tidak boleh dihapus';

    private Opd $opd;

    private string $userId;

    private string $formatId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = $this->makeOpd('UJI');
        $this->userId = $this->makeUser(null, 'super_admin')->id;
        $this->formatId = $this->insertFormat($this->opd->id, $this->userId);
    }

    private function issuedLetter(): string
    {
        return $this->insertLetter([
            'opd_id' => $this->opd->id,
            'format_nomor_surat_id' => $this->formatId,
            'dibuat_oleh' => $this->userId,
        ]);
    }

    private function cancelledLetter(): string
    {
        $id = $this->issuedLetter();

        DB::table('nomor_surat')->where('id', $id)->update([
            'status' => 'batal',
            'alasan_batal' => 'Salah ketik',
            'dibatalkan_oleh' => $this->userId,
            'batal_pada' => '2026-10-16 10:00:00',
        ]);

        return $id;
    }

    public function test_it_rejects_deleting_an_issued_letter(): void
    {
        $id = $this->issuedLetter();
        $before = $this->rawRow('nomor_surat', $id);

        $this->assertTriggerRejects(fn () => DB::table('nomor_surat')->where('id', $id)->delete(), self::MESSAGE);

        $this->assertSame($before, $this->rawRow('nomor_surat', $id));
    }

    public function test_it_rejects_deleting_a_cancelled_letter(): void
    {
        $id = $this->cancelledLetter();
        $before = $this->rawRow('nomor_surat', $id);

        $this->assertTriggerRejects(fn () => DB::table('nomor_surat')->where('id', $id)->delete(), self::MESSAGE);

        $this->assertSame($before, $this->rawRow('nomor_surat', $id));
    }

    public function test_it_rejects_deleting_through_the_model(): void
    {
        $id = $this->issuedLetter();
        $letter = NomorSurat::query()->withoutGlobalScope('opd')->findOrFail($id);

        $this->assertTriggerRejects(fn () => $letter->delete(), self::MESSAGE);

        $this->assertDatabaseHas('nomor_surat', ['id' => $id, 'status' => 'terbit']);
    }

    public function test_it_rejects_a_bulk_delete_and_keeps_every_row(): void
    {
        $this->issuedLetter();
        $this->issuedLetter();
        $this->cancelledLetter();

        $this->assertTriggerRejects(fn () => DB::table('nomor_surat')->delete(), self::MESSAGE);

        $this->assertSame(3, DB::table('nomor_surat')->count());
    }

    public function test_cancelled_numbers_stay_in_the_table_and_are_not_reused(): void
    {
        $cancelled = $this->cancelledLetter();

        $next = $this->issuedLetter();

        $this->assertSame(1, (int) $this->rawRow('nomor_surat', $cancelled)['nomor_urut']);
        $this->assertSame(2, (int) $this->rawRow('nomor_surat', $next)['nomor_urut']);
    }
}
