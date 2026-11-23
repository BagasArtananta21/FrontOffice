<?php

namespace Tests\Feature\Opd;

use App\Filament\SuperAdmin\Resources\Opds\Pages\ManageOpds;
use App\Models\Opd;
use App\Models\User;
use App\Services\DisplayDeviceService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class OpdDisplayProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdmin = new User();
        $superAdmin->forceFill([
            'name' => 'Super Admin Uji',
            'email' => 'super-uji@example.com',
            'password' => 'kata-sandi-uji-123',
            'role' => 'super_admin',
        ])->save();

        Filament::setCurrentPanel('super-admin');
        $this->actingAs($superAdmin);
    }

    private function createOpd()
    {
        return Livewire::test(ManageOpds::class)->callAction('create', data: [
            'kode_opd' => 'BARU',
            'nama_opd' => 'OPD Baru',
        ]);
    }

    public function test_creating_an_opd_also_creates_its_display_with_a_pin(): void
    {
        $this->createOpd()->assertHasNoFormErrors();

        $opd = Opd::where('kode_opd', 'BARU')->firstOrFail();
        $rows = DB::table('display_devices')->where('opd_id', $opd->id)->get();

        $this->assertCount(1, $rows);
        $this->assertNotNull($rows[0]->pin_hmac);
        $this->assertNull($rows[0]->token_hash);
    }

    public function test_no_opd_is_left_behind_when_its_display_cannot_be_created(): void
    {
        $this->mock(DisplayDeviceService::class, fn ($mock) => $mock
            ->shouldReceive('provisionFor')
            ->andThrow(new RuntimeException('Gagal membuat display.')));

        $this->createOpd();

        $this->assertFalse(Opd::where('kode_opd', 'BARU')->exists());
        $this->assertSame(0, DB::table('display_devices')->count());
    }
}
