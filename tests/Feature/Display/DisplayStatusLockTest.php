<?php

namespace Tests\Feature\Display;

use App\Filament\Admin\Widgets\DisplayStatus;
use App\Models\Opd;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class DisplayStatusLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $opd = Opd::create(['kode_opd' => 'UJI', 'nama_opd' => 'OPD Uji']);

        $adminFo = new User();
        $adminFo->forceFill([
            'name' => 'Admin Uji',
            'email' => 'admin-uji@example.com',
            'password' => 'kata-sandi-uji-123',
            'role' => 'admin_fo',
            'opd_id' => $opd->id,
        ])->save();

        Filament::setCurrentPanel('admin');
        $this->actingAs($adminFo);
    }

    public function test_server_side_updates_still_work(): void
    {
        Livewire::test(DisplayStatus::class)
            ->call('checkSignatureRequest')
            ->assertSet('watchedKunjunganId', null);
    }

    public function test_client_cannot_overwrite_the_watched_visit_id(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(DisplayStatus::class)->set('watchedKunjunganId', 'id-dari-klien');
    }
}
