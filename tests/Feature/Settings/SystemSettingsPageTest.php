<?php

namespace Tests\Feature\Settings;

use App\Filament\SuperAdmin\Pages\SystemSettings;
use App\Services\SystemSettingService as Settings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class SystemSettingsPageTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('super-admin');
    }

    public function test_a_super_admin_can_open_the_page(): void
    {
        $this->actingAs($this->makeUser(null, role: 'super_admin'))
            ->get('/super-admin/pengaturan-sistem')
            ->assertOk();
    }

    public function test_an_admin_fo_cannot_open_the_page(): void
    {
        $this->actingAs($this->makeUser($this->makeOpd()))
            ->get('/super-admin/pengaturan-sistem')
            ->assertForbidden();
    }

    public function test_the_form_starts_with_the_current_values_and_saves_changes(): void
    {
        $this->actingAs($this->makeUser(null, role: 'super_admin'));

        Livewire::test(SystemSettings::class)
            ->assertFormSet([Settings::SLIDE_MAX_COUNT => 3, Settings::IDLE_SCREENSAVER_MINUTES => 5])
            ->fillForm([Settings::SLIDE_MAX_COUNT => 4])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(4, app(Settings::class)->get(Settings::SLIDE_MAX_COUNT));
    }

    public function test_a_value_out_of_range_is_not_saved(): void
    {
        $this->actingAs($this->makeUser(null, role: 'super_admin'));

        Livewire::test(SystemSettings::class)
            ->fillForm([Settings::SLIDE_MAX_COUNT => 50])
            ->call('save')
            ->assertHasFormErrors([Settings::SLIDE_MAX_COUNT]);

        $this->assertDatabaseCount('pengaturan', 0);
    }
}