<?php

namespace Tests\Feature\Opd;

use App\Filament\SuperAdmin\Resources\Opds\Pages\ManageOpds;
use App\Models\Opd;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OpdLogoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

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

    private function createOpdWithLogo(UploadedFile $logo)
    {
        return Livewire::test(ManageOpds::class)->callAction('create', data: [
            'kode_opd' => 'LOGOUJI',
            'nama_opd' => 'OPD Logo Uji',
            'logo' => $logo,
        ]);
    }

    public static function acceptedLogos(): array
    {
        return [
            'png' => ['logo.png'],
            'jpeg' => ['logo.jpg'],
        ];
    }

    public static function rejectedLogos(): array
    {
        return [
            'svg' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><script>alert(1)</script></svg>'],
            'gif' => ['logo.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')],
        ];
    }

    #[DataProvider('acceptedLogos')]
    public function test_it_accepts_png_and_jpeg(string $name): void
    {
        $this->createOpdWithLogo(UploadedFile::fake()->image($name, 64, 64))
            ->assertHasNoFormErrors();

        $opd = Opd::where('kode_opd', 'LOGOUJI')->firstOrFail();

        $this->assertNotNull($opd->logo);
        Storage::disk('public')->assertExists($opd->logo);
    }

    #[DataProvider('rejectedLogos')]
    public function test_it_rejects_other_image_types(string $name, string $content): void
    {
        $this->createOpdWithLogo(UploadedFile::fake()->createWithContent($name, $content))
            ->assertHasFormErrors(['logo']);

        $this->assertDatabaseCount('opd', 0);
    }
}
