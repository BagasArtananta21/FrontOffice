<?php

namespace Tests\Feature\Numbering;

use App\Services\LetterNumberFormatter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LetterNumberFormatterTest extends TestCase
{
    private function formatter(): LetterNumberFormatter
    {
        return new LetterNumberFormatter();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function values(array $overrides = []): array
    {
        return [
            'klasifikasi' => '443.32',
            'nomor_urut' => 102,
            'sub_nomor' => 0,
            'bidang' => 'PKP',
            'kode_opd' => 'DISKOMINFOSANTI',
            'tanggal' => CarbonImmutable::create(2026, 10, 15),
            ...$overrides,
        ];
    }

    private function full(): array
    {
        return [
            ['jenis' => 'klasifikasi'],
            ['jenis' => 'urut'],
            ['jenis' => 'bidang'],
            ['jenis' => 'kode_opd'],
            ['jenis' => 'bulan_romawi'],
            ['jenis' => 'tahun'],
        ];
    }

    public function test_it_renders_the_diskominfosanti_example_from_the_documentation(): void
    {
        $this->assertSame(
            '443.32/102/PKP/DISKOMINFOSANTI/X/2026',
            $this->formatter()->format($this->full(), $this->values()),
        );
    }

    public static function singleSegments(): array
    {
        return [
            'klasifikasi' => [[['jenis' => 'klasifikasi']], '443.32'],
            'urut' => [[['jenis' => 'urut']], '102'],
            'bidang' => [[['jenis' => 'bidang']], 'PKP'],
            'kode_opd' => [[['jenis' => 'kode_opd']], 'DISKOMINFOSANTI'],
            'bulan_romawi' => [[['jenis' => 'bulan_romawi']], 'X'],
            'tahun' => [[['jenis' => 'tahun']], '2026'],
            'teks' => [[['jenis' => 'teks', 'teks' => 'RAHASIA']], 'RAHASIA'],
        ];
    }

    #[DataProvider('singleSegments')]
    public function test_it_renders_each_segment_type(array $susunan, string $expected): void
    {
        $this->assertSame($expected, $this->formatter()->format($susunan, $this->values()));
    }

    public static function romanMonths(): array
    {
        return [
            'january' => [1, 'I'],
            'february' => [2, 'II'],
            'march' => [3, 'III'],
            'april' => [4, 'IV'],
            'may' => [5, 'V'],
            'june' => [6, 'VI'],
            'july' => [7, 'VII'],
            'august' => [8, 'VIII'],
            'september' => [9, 'IX'],
            'october' => [10, 'X'],
            'november' => [11, 'XI'],
            'december' => [12, 'XII'],
        ];
    }

    #[DataProvider('romanMonths')]
    public function test_it_writes_the_month_of_the_date_in_roman_numerals(int $month, string $expected): void
    {
        $date = CarbonImmutable::create(2026, $month, 1);

        $this->assertSame($expected, $this->formatter()->format([['jenis' => 'bulan_romawi']], $this->values(['tanggal' => $date])));
    }

    public function test_it_takes_month_and_year_from_the_given_date_only(): void
    {
        $this->travelTo('2030-03-03 10:00:00');

        $number = $this->formatter()->format(
            [['jenis' => 'bulan_romawi'], ['jenis' => 'tahun']],
            $this->values(['tanggal' => CarbonImmutable::create(2025, 12, 31)]),
        );

        $this->assertSame('XII/2025', $number);
    }

    public function test_it_joins_with_a_slash_and_does_not_pad_numbers(): void
    {
        $number = $this->formatter()->format(
            [['jenis' => 'urut'], ['jenis' => 'tahun']],
            $this->values(['nomor_urut' => 7]),
        );

        $this->assertSame('7/2026', $number);
    }

    public static function supplementaryNumbers(): array
    {
        return [
            'parent only' => [5, 0, '5'],
            'first supplementary' => [102, 1, '102.1'],
            'tenth supplementary' => [102, 10, '102.10'],
        ];
    }

    #[DataProvider('supplementaryNumbers')]
    public function test_it_renders_a_supplementary_number_as_number_dot_sub_number(int $number, int $sub, string $expected): void
    {
        $this->assertSame(
            $expected,
            $this->formatter()->format([['jenis' => 'urut']], $this->values(['nomor_urut' => $number, 'sub_nomor' => $sub])),
        );
    }

    public function test_it_follows_the_order_of_the_template(): void
    {
        $number = $this->formatter()->format(
            [['jenis' => 'tahun'], ['jenis' => 'teks', 'teks' => 'SK'], ['jenis' => 'urut'], ['jenis' => 'teks', 'teks' => 'SK']],
            $this->values(),
        );

        $this->assertSame('2026/SK/102/SK', $number);
    }

    public static function emptySegments(): array
    {
        return [
            'bidang is null' => [[['jenis' => 'bidang']], ['bidang' => null], 'bidang'],
            'bidang is empty' => [[['jenis' => 'bidang']], ['bidang' => ''], 'bidang'],
            'klasifikasi is empty' => [[['jenis' => 'klasifikasi']], ['klasifikasi' => ''], 'klasifikasi'],
            'text segment without text' => [[['jenis' => 'teks']], [], 'teks'],
            'text segment with blank text' => [[['jenis' => 'teks', 'teks' => '  ']], [], 'teks'],
            'unknown segment type' => [[['jenis' => 'tidak_dikenal']], [], 'tidak_dikenal'],
            'segment without a type' => [[[]], [], ''],
        ];
    }

    #[DataProvider('emptySegments')]
    public function test_it_throws_instead_of_producing_a_number_with_an_empty_segment(array $susunan, array $overrides, string $segment): void
    {
        try {
            $this->formatter()->format($susunan, $this->values($overrides));
            $this->fail('Seharusnya melempar InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame("Nilai segmen [{$segment}] kosong.", $e->getMessage());
        }
    }

    public function test_one_empty_segment_spoils_the_whole_number_even_when_the_others_are_fine(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->formatter()->format($this->full(), $this->values(['bidang' => null]));
    }

    public function test_it_accepts_a_mutable_carbon_date_as_well(): void
    {
        /** @var CarbonInterface $date */
        $date = now()->setDate(2026, 4, 9);

        $this->assertSame('IV/2026', $this->formatter()->format(
            [['jenis' => 'bulan_romawi'], ['jenis' => 'tahun']],
            $this->values(['tanggal' => $date]),
        ));
    }

    public function test_preview_uses_placeholder_values_and_the_current_month(): void
    {
        $this->travelTo('2026-08-20 09:00:00');

        $this->assertSame('001/1/KODEBIDANG/KODEOPD/VIII/2026', $this->formatter()->preview($this->full()));
        $this->assertSame('001/1/KODEBIDANG/UJI/VIII/2026', $this->formatter()->preview($this->full(), 'UJI'));
    }
}
