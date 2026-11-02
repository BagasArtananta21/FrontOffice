<?php

namespace Tests\Feature\Tenant;

use App\Filament\SuperAdmin\Resources\Users\Pages\ManageUsers;
use App\Models\Opd;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminSelfEditTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = new User();
        $this->superAdmin->forceFill([
            'name' => 'Super Admin Uji',
            'email' => 'super-uji@example.com',
            'password' => 'kata-sandi-uji-123',
            'role' => 'super_admin',
            'aktif' => true,
        ])->save();

        Filament::setCurrentPanel('super-admin');
        $this->actingAs($this->superAdmin);
    }

    public function test_role_and_active_fields_are_disabled_on_own_account(): void
    {
        Livewire::test(ManageUsers::class)
            ->mountAction(TestAction::make('edit')->table($this->superAdmin))
            ->assertFormFieldDisabled('role')
            ->assertFormFieldDisabled('aktif');
    }

    public function test_own_role_and_active_status_stay_unchanged_after_saving(): void
    {
        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($this->superAdmin), data: [
                'name' => 'Nama Baru',
            ])
            ->assertHasNoFormErrors();

        $fresh = $this->superAdmin->fresh();

        $this->assertSame('Nama Baru', $fresh->name);
        $this->assertSame('super_admin', $fresh->role);
        $this->assertTrue((bool) $fresh->aktif);
    }

    public function test_fields_stay_editable_on_another_account(): void
    {
        $other = new User();
        $other->forceFill([
            'name' => 'Super Admin Lain',
            'email' => 'lain@example.com',
            'password' => 'kata-sandi-uji-123',
            'role' => 'super_admin',
        ])->save();

        Livewire::test(ManageUsers::class)
            ->mountAction(TestAction::make('edit')->table($other))
            ->assertFormFieldEnabled('role')
            ->assertFormFieldEnabled('aktif');
    }
}
