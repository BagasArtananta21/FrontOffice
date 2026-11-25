<?php

namespace Tests\Feature\Opd;

use App\Filament\SuperAdmin\Resources\Opds\OpdResource;
use App\Filament\SuperAdmin\Resources\Opds\Pages\ManageOpds;
use App\Filament\SuperAdmin\Widgets\AttentionList;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\BuildsFixtures;
use Tests\TestCase;

class OpdDisplayStatusTest extends TestCase
{
    use BuildsFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('super-admin');
        $this->actingAs($this->makeUser(null, role: 'super_admin'));
    }

    public function test_the_opd_table_shows_the_display_state_of_each_opd(): void
    {
        $connected = $this->makeOpd('SAMBUNG');
        $disconnected = $this->makeOpd('PUTUS');
        $unpaired = $this->makeOpd('KOSONG');
        $this->insertDisplay($connected->id, 'token-sambung');
        $this->insertDisplay($disconnected->id, 'token-putus', ['terakhir_aktif' => now()->subHour()]);
        $this->insertDisplay($unpaired->id, 'token-kosong', ['token_hash' => null, 'terakhir_aktif' => null]);

        Livewire::test(ManageOpds::class)
            ->assertTableColumnStateSet('display_status', 'Terhubung', $connected)
            ->assertTableColumnStateSet('display_status', 'Tidak terhubung', $disconnected)
            ->assertTableColumnStateSet('display_status', 'Belum terpasang', $unpaired);
    }

    public function test_the_attention_list_links_a_display_problem_to_the_opd_page(): void
    {
        $opd = $this->makeOpd('KOSONG');
        $this->insertDisplay($opd->id, 'token-kosong', ['token_hash' => null, 'terakhir_aktif' => null]);

        Livewire::test(AttentionList::class)
            ->assertSee('Display KOSONG belum dipasang')
            ->assertSee(OpdResource::getUrl(), false);
    }

    public function test_the_old_display_page_is_gone(): void
    {
        $this->get('/super-admin/display')->assertNotFound();
    }
}