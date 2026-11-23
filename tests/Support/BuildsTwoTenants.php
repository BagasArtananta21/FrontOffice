<?php

namespace Tests\Support;

use App\Models\Opd;
use App\Models\User;
use App\Services\DisplayDeviceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

/**
 * Two OPDs ("a" and "b"), each with an admin, two bidang, two visits, two
 * display devices (tokens token-a/token-b ), two
 * format versions and two letter numbers. Counters come from the triggers.
 */
trait BuildsTwoTenants
{
    use BuildsFixtures;

    protected Opd $opdA;

    protected Opd $opdB;

    protected User $superAdmin;

    protected User $adminA;

    protected User $adminB;

    protected const DISPLAY_PIN = '482913';

    protected function giveDevicePin(string $pin = self::DISPLAY_PIN): void 
    {
        $this->updateDevice([
            'pin' => Crypt::encryptString($pin),
            'pin_hmac' => DisplayDeviceService::hmac($pin),
        ]);
    }

    protected function updateDevice(array $overrides): void
    {
        $this->updateDevice(['token_hash' => null]);
    }

    protected function seedTwoTenants(): void
    {
        $this->opdA = $this->makeOpd('AAA');
        $this->opdB = $this->makeOpd('BBB');
        $this->superAdmin = $this->makeUser(null, 'super_admin');
        $this->adminA = $this->makeUser($this->opdA);
        $this->adminB = $this->makeUser($this->opdB);

        foreach (['a' => $this->opdA, 'b' => $this->opdB] as $key => $opd) {
            $this->insertBidang($opd->id, 'PKP');
            $this->insertBidang($opd->id, 'SDP');

            $firstFormat = $this->insertFormat($opd->id, $this->superAdmin->id, '2026-01-01');
            $this->insertFormat($opd->id, $this->superAdmin->id, '2026-06-01');

            $this ->insertDisplay($opd->id, "token-{$key}");

            $this->insertVisit($opd->id, '2026-10-15 09:00:00');
            $this->insertVisit($opd->id, '2026-10-15 10:00:00');

            $this->insertLetter(['opd_id' => $opd->id, 'format_nomor_surat_id' => $firstFormat, 'dibuat_oleh' => $this->superAdmin->id]);
            $this->insertLetter(['opd_id' => $opd->id, 'format_nomor_surat_id' => $firstFormat, 'dibuat_oleh' => $this->superAdmin->id]);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function rowIdsOf(string $table, Opd $opd): array
    {
        return DB::table($table)->where('opd_id', $opd->id)->orderBy('id')->pluck('id')->all();
    }
}
