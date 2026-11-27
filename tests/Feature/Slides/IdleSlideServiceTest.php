<?php

namespace Tests\Feature\Slides;

use App\Exceptions\SlideStorageException;
use App\Models\Opd;
use App\Models\SlideIdle;
use App\Models\User;
use App\Services\IdleSlideService;
use App\Services\SystemSettingService as Settings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsFixtures;
use Tests\Support\BuildsSlides;
use Tests\TestCase;

class IdleSlideServiceTest extends TestCase
{
    use BuildsFixtures, BuildsSlides, RefreshDatabase;

    private Opd $opd;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->opd = $this->makeOpd('UJI');
        $this->officer = $this->makeUser($this->opd);
        $this->actingAs($this->officer);
    }

    private function service(): IdleSlideService
    {
        return app(IdleSlideService::class);
    }

    private function png(string $name = 'slide.png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 192, 108);
    }

    /**
     * A real file on disk, so the MIME type is sniffed from the content. The
     * Laravel fake file derives it from the file name instead.
     */
    private function realFile(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'slide');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function assertRejected(UploadedFile $file): void
    {
        try {
            $this->service()->add($file);
            $this->fail('InvalidArgumentException tidak dilempar.');
        } catch (InvalidArgumentException) {
        }

        $this->assertDatabaseCount('slide_idle', 0);
        $this->assertSame([], $this->storedSlideFiles());
    }

    public function test_slides_are_stored_and_numbered_in_upload_order(): void
    {
        $first = $this->service()->add($this->png());
        $second = $this->service()->add(UploadedFile::fake()->image('foto.jpg', 192, 108));
        $third = $this->service()->add($this->png());

        $this->assertSame([1, 2, 3], [$first->urutan, $second->urutan, $third->urutan]);
        $this->assertSame($this->opd->id, $first->opd_id);
        $this->assertMatchesRegularExpression('#^slide-idle/[0-9a-f-]{36}\.png$#', $first->gambar);
        $this->assertMatchesRegularExpression('#^slide-idle/[0-9a-f-]{36}\.jpg$#', $second->gambar);
        Storage::disk('public')->assertExists([$first->gambar, $second->gambar, $third->gambar]);
    }

    public function test_a_new_slide_goes_after_the_highest_position_even_with_gaps(): void
    {
        $this->insertSlide($this->opd->id, 7);

        $this->assertSame(8, $this->service()->add($this->png())->urutan);
    }

    public function test_the_limit_from_the_settings_is_enforced_without_leaving_a_file(): void
    {
        app(Settings::class)->update([Settings::SLIDE_MAX_COUNT => 2]);
        $this->service()->add($this->png());
        $this->service()->add($this->png());

        try {
            $this->service()->add($this->png());
            $this->fail('InvalidArgumentException tidak dilempar.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Jumlah slide sudah mencapai batas (2).', $e->getMessage());
        }

        $this->assertDatabaseCount('slide_idle', 2);
        $this->assertCount(2, $this->storedSlideFiles());
    }

    public function test_lowering_the_limit_keeps_existing_slides_and_blocks_new_ones(): void
    {
        $this->service()->add($this->png());
        $this->service()->add($this->png());
        app(Settings::class)->update([Settings::SLIDE_MAX_COUNT => 1]);

        try {
            $this->service()->add($this->png());
            $this->fail('InvalidArgumentException tidak dilempar.');
        } catch (InvalidArgumentException) {
        }

        $this->assertDatabaseCount('slide_idle', 2);
    }

    public function test_the_limit_is_counted_per_opd(): void
    {
        app(Settings::class)->update([Settings::SLIDE_MAX_COUNT => 1]);
        $this->service()->add($this->png());

        $otherOpd = $this->makeOpd('LAIN');
        $this->actingAs($this->makeUser($otherOpd));

        $slide = $this->service()->add($this->png());

        $this->assertSame($otherOpd->id, $slide->opd_id);
        $this->assertSame(1, $slide->urutan);
        $this->assertDatabaseCount('slide_idle', 2);
    }

    public static function disallowedContents(): array
    {
        return [
            'svg named png' => ['foto.png', '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><script>alert(1)</script></svg>'],
            'gif named jpg' => ['foto.jpg', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')],
            'text named png' => ['foto.png', 'bukan gambar'],
        ];
    }

    #[DataProvider('disallowedContents')]
    public function test_the_type_is_judged_from_the_content_not_the_name(string $name, string $content): void
    {
        $this->assertRejected($this->realFile($name, $content));
    }

    public function test_the_extension_follows_the_content_not_the_name(): void
    {
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        $slide = $this->service()->add($this->realFile('berbahaya.php', $pngBytes));

        $this->assertStringEndsWith('.png', $slide->gambar);
    }

    public function test_a_file_larger_than_the_configured_size_is_rejected(): void
    {
        app(Settings::class)->update([Settings::SLIDE_MAX_SIZE_MB => 1]);

        $this->assertRejected($this->png()->size(1025));
    }

    public function test_a_file_of_exactly_the_configured_size_is_accepted(): void
    {
        app(Settings::class)->update([Settings::SLIDE_MAX_SIZE_MB => 1]);

        $this->service()->add($this->png()->size(1024));

        $this->assertDatabaseCount('slide_idle', 1);
    }

    public function test_a_failed_disk_write_leaves_no_row(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::set('public', $disk);

        try {
            $this->service()->add($this->png());
            $this->fail('SlideStorageException tidak dilempar.');
        } catch (SlideStorageException) {
        }

        $this->assertDatabaseCount('slide_idle', 0);
    }

    public function test_remove_deletes_the_row_and_the_file(): void
    {
        $slide = $this->service()->add($this->png());

        $this->service()->remove($slide);

        $this->assertDatabaseCount('slide_idle', 0);
        $this->assertSame([], $this->storedSlideFiles());
    }

    public function test_a_file_that_cannot_be_deleted_is_reported_and_the_row_still_goes(): void
    {
        $slide = SlideIdle::findOrFail($this->insertSlide($this->opd->id, 1));

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->andReturn(false);
        Storage::set('public', $disk);
        Exceptions::fake();

        $this->service()->remove($slide);

        Exceptions::assertReported(SlideStorageException::class);
        $this->assertDatabaseCount('slide_idle', 0);
    }

    public function test_adding_without_an_opd_context_is_refused(): void
    {
        auth()->logout();

        $this->expectException(AuthorizationException::class);

        $this->service()->add($this->png());
    }

    public function test_a_super_admin_has_no_opd_context_to_add_slides(): void
    {
        $this->actingAs($this->makeUser(null, 'super_admin'));

        $this->expectException(AuthorizationException::class);

        $this->service()->add($this->png());
    }
}
