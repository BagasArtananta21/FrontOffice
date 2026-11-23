<?php

namespace Tests\Feature\Seeding;

use Database\Seeders\DisplayDeviceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class DisplayDeviceSeederTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    public function test_every_opd_gets_exactly_one_display_and_rerunning_adds_nothing(): void
    {
        $opds = [$this->makeOpd('AAA'), $this->makeOpd('BBB')];

        $this->seed(DisplayDeviceSeeder::class);
        $this->seed(DisplayDeviceSeeder::class);

        foreach ($opds as $opd) {
            $rows = DB::table('display_devices')->where('opd_id', $opd->id)->get();

            $this->assertCount(1, $rows);
            $this->assertNotNull($rows[0]->pin_hmac);
            $this->assertTrue((bool) $rows[0]->aktif);
        }
    }

    public function test_an_opd_that_already_has_a_display_is_left_alone(): void
    {
        $opd = $this->makeOpd();
        $this->insertDisplay($opd->id, 'token-lama');

        $this->seed(DisplayDeviceSeeder::class);

        $this->assertSame(1, DB::table('display_devices')->where('opd_id', $opd->id)->count());
        $this->assertSame(hash('sha256', 'token-lama'), DB::table('display_devices')->where('opd_id', $opd->id)->value('token_hash'));
    }
}
