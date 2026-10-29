<?php

namespace Tests\Support;

use App\Models\NomorSurat;
use App\Models\Opd;
use App\Models\User;
use App\Services\LetterNumberService;

/**
 * Opd UJI with an admin_fo (logged in), a bidang PKP and one number format
 * in force since 2026-01-01 (KLASIFIKASI/URUT/BIDANG/KODEOPD/BULAN/TAHUN).
 * Call setUpLetters() from setUp().
 */
trait PreparesLetterIssuing
{
    use BuildsFixtures;

    protected Opd $opd;

    protected User $admin;

    protected string $creatorId;

    protected string $bidangId;

    protected string $formatId;

    protected function setUpLetters(bool $izinkanSusulan = false, string $berlakuSejak = '2026-01-01'): void
    {
        $this->opd = $this->makeOpd('UJI');
        $this->admin = $this->makeUser($this->opd);
        $this->creatorId = $this->makeUser(null, 'super_admin')->id;
        $this->bidangId = $this->insertBidang($this->opd->id, 'PKP');
        $this->formatId = $this->insertFormat($this->opd->id, $this->creatorId, $berlakuSejak, $izinkanSusulan);

        $this->actingAs($this->admin);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function letterData(array $overrides = []): array
    {
        return [
            'kode_klasifikasi' => '443.32',
            'bidang_id' => $this->bidangId,
            'perihal' => 'Undangan rapat',
            'tujuan_surat' => 'Dinas Pendidikan',
            'nama_peminta' => 'Pak Budi',
            'tanggal_surat' => today()->toDateString(),
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function issueLetter(array $overrides = [], ?User $by = null): NomorSurat
    {
        return app(LetterNumberService::class)->issue($this->letterData($overrides), $by ?? $this->admin);
    }

    protected function issueOn(string $now, string $letterDate): NomorSurat
    {
        $this->travelTo($now);

        return $this->issueLetter(['tanggal_surat' => $letterDate]);
    }
}
