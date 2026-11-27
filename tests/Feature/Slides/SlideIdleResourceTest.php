<?php

namespace Tests\Feature\Slides;

use App\Exceptions\SlideStorageException;
use App\Filament\Admin\Resources\SlideIdles\Pages\ManageSlideIdles;
use App\Models\Opd;
use App\Models\SlideIdle;
use App\Models\User;
use App\Services\IdleSlideService;
use App\Services\SystemSettingService as Settings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\BuildsFixtures;
use Tests\Support\BuildsSlides;
use Tests\TestCase;

class SlideIdleResourceTest extends TestCase
{
    use BuildsFixtures, BuildsSlides, RefreshDatabase;

    private Opd $opd;

    private Opd $otherOpd;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->withoutVite();

        $this->opd = $this->makeOpd('UJI');
        $this->otherOpd = $this->makeOpd('LAIN');
        $this->officer = $this->makeUser($this->opd);

        Filament::setCurrentPanel('admin');
        $this->actingAs($this->officer);
    }

    private function upload(UploadedFile $file)
    {
        return Livewire::test(ManageSlideIdles::class)->callAction('create', data: ['gambar' => $file]);
    }

    /**
     * @return \Closure(string, array<int|string, mixed>): bool
     */
    private function swalWith(string $icon, string $title): \Closure
    {
        return function (string $event, array $params) use ($icon, $title): bool {
            $payload = $params[0] ?? $params;

            return ($payload['icon'] ?? null) === $icon && ($payload['title'] ?? null) === $title;
        };
    }

    public function test_officer_can_open_the_page(): void
    {
        $this->get('/admin/slide-layar-idle')->assertOk();
    }

    public function test_a_super_admin_cannot_open_the_page(): void
    {
        $this->actingAs($this->makeUser(null, 'super_admin'))
            ->get('/admin/slide-layar-idle')
            ->assertForbidden();
    }

    public function test_an_upload_creates_one_slide_with_exactly_one_stored_file(): void
    {
        $this->upload(UploadedFile::fake()->image('a.png', 192, 108))
            ->assertHasNoFormErrors()
            ->assertDispatched('swal', $this->swalWith('success', 'Slide Ditambahkan'));

        $this->assertDatabaseCount('slide_idle', 1);
        $slide = SlideIdle::query()->firstOrFail();
        $this->assertSame($this->opd->id, $slide->opd_id);

        // One file only: Filament must not keep its own copy next to the service's.
        $this->assertSame([$slide->gambar], $this->storedSlideFiles());
    }

    public static function rejectedFiles(): array
    {
        return [
            'svg' => ['slide.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><script>alert(1)</script></svg>'],
            'gif' => ['slide.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')],
        ];
    }

    #[DataProvider('rejectedFiles')]
    public function test_the_form_rejects_other_file_types(string $name, string $content): void
    {
        $this->upload(UploadedFile::fake()->createWithContent($name, $content))
            ->assertHasFormErrors(['gambar']);

        $this->assertDatabaseCount('slide_idle', 0);
        $this->assertSame([], $this->storedSlideFiles());
    }

    public function test_the_form_size_limit_follows_the_setting(): void
    {
        app(Settings::class)->update([Settings::SLIDE_MAX_SIZE_MB => 1]);

        $this->upload(UploadedFile::fake()->image('a.png', 192, 108)->size(1500))
            ->assertHasFormErrors(['gambar']);

        $this->assertDatabaseCount('slide_idle', 0);
    }

    public function test_the_create_button_is_disabled_when_the_limit_is_reached(): void
    {
        app(Settings::class)->update([Settings::SLIDE_MAX_COUNT => 2]);
        $this->insertSlide($this->opd->id, 1);

        Livewire::test(ManageSlideIdles::class)->assertActionEnabled('create');

        $this->insertSlide($this->opd->id, 2);

        Livewire::test(ManageSlideIdles::class)->assertActionDisabled('create');
    }

    public function test_slides_of_another_opd_do_not_count_toward_the_button(): void
    {
        app(Settings::class)->update([Settings::SLIDE_MAX_COUNT => 1]);
        $this->insertSlide($this->otherOpd->id, 1);

        Livewire::test(ManageSlideIdles::class)->assertActionEnabled('create');
    }

    public function test_the_table_shows_only_the_slides_of_the_own_opd_in_order(): void
    {
        $second = $this->insertSlide($this->opd->id, 2);
        $first = $this->insertSlide($this->opd->id, 1);
        $foreign = $this->insertSlide($this->otherOpd->id, 1);

        Livewire::test(ManageSlideIdles::class)
            ->assertCanSeeTableRecords(
                SlideIdle::query()->orderBy('urutan')->get(),
                inOrder: true,
            )
            ->assertCanNotSeeTableRecords([SlideIdle::withoutGlobalScope('opd')->findOrFail($foreign)])
            ->assertCountTableRecords(2);

        $this->assertSame([1, 2], [$this->slideOrder($first), $this->slideOrder($second)]);
    }

    public function test_reordering_changes_the_positions_and_ignores_another_opds_slide(): void
    {
        $a = $this->insertSlide($this->opd->id, 1);
        $b = $this->insertSlide($this->opd->id, 2);
        $foreign = $this->insertSlide($this->otherOpd->id, 9);

        // The other OPD's id is slipped into the request, as a crafted client could do.
        Livewire::test(ManageSlideIdles::class)->call('reorderTable', [$foreign, $b, $a]);

        $this->assertLessThan($this->slideOrder($a), $this->slideOrder($b));
        $this->assertSame(9, $this->slideOrder($foreign));
    }

    public function test_the_remove_action_deletes_the_slide_and_its_file(): void
    {
        $slide = SlideIdle::findOrFail($this->insertSlide($this->opd->id, 1));

        Livewire::test(ManageSlideIdles::class)
            ->callAction(TestAction::make('remove')->table($slide))
            ->assertDispatched('swal', $this->swalWith('success', 'Slide Dihapus'));

        $this->assertDatabaseCount('slide_idle', 0);
        $this->assertSame([], $this->storedSlideFiles());
    }

    public function test_a_failed_disk_write_shows_an_error_and_is_reported(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::set('public', $disk);
        Exceptions::fake();

        $this->upload(UploadedFile::fake()->image('a.png', 192, 108))
            ->assertDispatched('swal', $this->swalWith('error', 'Gagal Menambahkan Slide'));

        Exceptions::assertReported(SlideStorageException::class);
        $this->assertDatabaseCount('slide_idle', 0);
    }

    // BUG: ManageSlideIdles does not catch QueryException, so a database error
    // surfaces as a raw error instead of the SweetAlert other pages show.
    #[Group('known-bug')]
    public function test_a_database_error_shows_an_error_and_is_reported(): void
    {
        $this->mock(IdleSlideService::class)
            ->shouldReceive('add')
            ->andThrow(new QueryException('mysql', 'insert into slide_idle', [], new \Exception('galat uji')));
        Exceptions::fake();

        $this->upload(UploadedFile::fake()->image('a.png', 192, 108))
            ->assertDispatched('swal', $this->swalWith('error', 'Gagal Menambahkan Slide'));

        Exceptions::assertReported(QueryException::class);
    }
}
