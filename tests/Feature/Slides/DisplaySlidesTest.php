<?php

namespace Tests\Feature\Slides;

use App\Services\SystemSettingService as Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsSlides;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

/**
 * What the display page receives for its idle screen. The rotation itself runs
 * in JavaScript and is not covered here.
 */
class DisplaySlidesTest extends TestCase
{
    use BuildsSlides, InteractsWithDisplay, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->withoutVite();
        $this->setUpDisplay();
    }

    public function test_the_display_shows_its_own_slides_in_order(): void
    {
        $second = $this->insertSlide($this->opd->id, 2, 'slide-idle/kedua.png');
        $first = $this->insertSlide($this->opd->id, 1, 'slide-idle/pertama.png');
        $this->insertSlide($this->makeOpd('LAIN')->id, 1, 'slide-idle/milik-opd-lain.png');

        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('slides', fn ($slides) => $slides->pluck('id')->all() === [$first, $second])
            ->assertSeeInOrder([asset('storage/slide-idle/pertama.png'), asset('storage/slide-idle/kedua.png')], false)
            ->assertDontSee('milik-opd-lain');
    }

    public function test_the_slideshow_receives_the_slide_count_and_the_configured_duration(): void
    {
        app(Settings::class)->update([Settings::SLIDE_DURATION_SECONDS => 12]);
        $this->insertSlide($this->opd->id, 1);
        $this->insertSlide($this->opd->id, 2);

        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertViewHas('slideSeconds', 12)
            ->assertSee('x-data="slideshow(2, 12)"', false);
    }

    public function test_without_slides_the_idle_screen_shows_the_opd_name_and_a_placeholder_logo(): void
    {
        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertSee($this->opd->nama_opd)
            ->assertSee(asset('images/icons/logo-placeholder.svg'), false)
            ->assertDontSee('slideshow(', false)
            ->assertDontSee('profil.mp4', false);
    }

    public function test_without_slides_the_opd_logo_is_used_when_there_is_one(): void
    {
        DB::table('opd')->where('id', $this->opd->id)->update(['logo' => 'logo-opd/uji.png']);

        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertSee(asset('storage/logo-opd/uji.png'), false)
            ->assertDontSee('logo-placeholder.svg', false);
    }

    public function test_the_signature_screen_has_no_idle_slideshow(): void
    {
        $this->insertSlide($this->opd->id, 1);
        $this->makeWaitingVisit();

        $this->asDisplay()->get(route('display.show'))
            ->assertOk()
            ->assertDontSee('slideshow(', false);
    }
}
