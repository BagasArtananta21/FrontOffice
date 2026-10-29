<?php

namespace Tests\Feature\Numbering;

use App\Models\NomorSurat;
use App\Services\LetterNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PreparesLetterIssuing;
use Tests\TestCase;

class LetterCancelTest extends TestCase
{
    use PreparesLetterIssuing, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLetters();
        $this->travelTo('2026-10-15 10:00:00');
    }

    private function service(): LetterNumberService
    {
        return app(LetterNumberService::class);
    }

    public function test_it_cancels_an_issued_letter_once_and_records_reason_officer_and_time(): void
    {
        $letter = $this->issueLetter();
        $canceller = $this->makeUser($this->opd);
        $this->travelTo('2026-10-16 08:15:00');

        $this->assertTrue($this->service()->cancel($letter, 'Salah ketik perihal', $canceller));

        $this->assertDatabaseHas('nomor_surat', [
            'id' => $letter->id,
            'status' => 'batal',
            'alasan_batal' => 'Salah ketik perihal',
            'dibatalkan_oleh' => $canceller->id,
            'batal_pada' => '2026-10-16 08:15:00',
            'nomor_urut' => 1,
            'nomor_lengkap' => '443.32/1/PKP/UJI/X/2026',
            'dibuat_oleh' => $this->admin->id,
        ]);
    }

    public function test_a_second_cancel_returns_false_and_keeps_the_first_cancellation(): void
    {
        $letter = $this->issueLetter();
        $other = $this->makeUser($this->opd);
        $this->service()->cancel($letter, 'Alasan pertama', $this->admin);
        $before = (array) DB::table('nomor_surat')->where('id', $letter->id)->first();

        $this->assertFalse($this->service()->cancel($letter, 'Alasan kedua', $other));

        $this->assertSame($before, (array) DB::table('nomor_surat')->where('id', $letter->id)->first());
    }

    public function test_it_returns_false_for_a_letter_that_does_not_exist(): void
    {
        $ghost = new NomorSurat();
        $ghost->id = (string) Str::uuid7();

        $this->assertFalse($this->service()->cancel($ghost, 'Tidak ada', $this->admin));
    }

    public function test_it_cannot_cancel_a_letter_of_another_opd(): void
    {
        $other = $this->makeOpd('LAIN');
        $otherAdmin = $this->makeUser($other);
        $otherBidang = $this->insertBidang($other->id, 'SDP');
        $this->insertFormat($other->id, $this->creatorId);
        $this->actingAs($otherAdmin);
        $foreign = $this->issueLetter(['bidang_id' => $otherBidang], $otherAdmin);

        $this->actingAs($this->admin);

        $this->assertFalse($this->service()->cancel($foreign, 'Bukan milik saya', $this->admin));
        $this->assertDatabaseHas('nomor_surat', ['id' => $foreign->id, 'status' => 'terbit', 'alasan_batal' => null]);
    }

    public function test_a_cancelled_number_is_never_reused(): void
    {
        $first = $this->issueLetter();
        $this->service()->cancel($first, 'Salah ketik', $this->admin);

        $next = $this->issueLetter();

        $this->assertSame(2, $next->nomor_urut);
        $this->assertSame('443.32/2/PKP/UJI/X/2026', $next->nomor_lengkap);
        $this->assertSame(2, $this->counterValue($this->opd->id, 'surat', '2026'));
        $this->assertSame('batal', NomorSurat::findOrFail($first->id)->status);
        $this->assertSame(2, NomorSurat::count());
    }

    public function test_cancelling_the_latest_number_does_not_roll_the_counter_back(): void
    {
        $this->issueLetter();
        $last = $this->issueLetter();

        $this->service()->cancel($last, 'Salah ketik', $this->admin);

        $this->assertSame(2, $this->counterValue($this->opd->id, 'surat', '2026'));
        $this->assertSame(3, $this->issueLetter()->nomor_urut);
    }

    public function test_cancelled_letters_stay_listed_in_book_order_by_year_number_and_sub_number(): void
    {
        $this->setUpSupplementaryFormat();

        $this->issueOn('2026-10-05 09:00:00', '2026-10-05');
        $second = $this->issueOn('2026-10-05 10:00:00', '2026-10-05');
        $this->issueOn('2026-10-10 09:00:00', '2026-10-10');
        $this->issueOn('2026-10-12 09:00:00', '2026-10-12');
        $this->service()->cancel($second, 'Salah ketik', $this->admin);
        $this->issueOn('2026-10-20 09:00:00', '2026-10-07');
        $this->issueOn('2026-10-21 09:00:00', '2026-10-07');
        $this->issueOn('2026-10-22 09:00:00', '2025-12-20');

        $book = NomorSurat::query()
            ->orderBy('tahun')->orderBy('nomor_urut')->orderBy('sub_nomor')
            ->get()
            ->map(fn (NomorSurat $letter) => "{$letter->tahun}:{$letter->nomor_urut}.{$letter->sub_nomor}:{$letter->status}")
            ->all();

        $this->assertSame([
            '2025:1.0:terbit',
            '2026:1.0:terbit',
            '2026:2.0:batal',
            '2026:2.1:terbit',
            '2026:2.2:terbit',
            '2026:3.0:terbit',
            '2026:4.0:terbit',
        ], $book);
    }

    public function test_numbers_above_nine_follow_numeric_order_in_book_order(): void
    {
        foreach (range(1, 11) as $ignored) {
            $this->issueLetter();
        }

        $numbers = NomorSurat::query()
            ->orderBy('tahun')->orderBy('nomor_urut')->orderBy('sub_nomor')
            ->pluck('nomor_urut')
            ->all();

        $this->assertSame(range(1, 11), $numbers);
    }

    private function setUpSupplementaryFormat(): void
    {
        DB::table('format_nomor_surat')->where('id', $this->formatId)->update(['izinkan_susulan' => true]);
    }
}
