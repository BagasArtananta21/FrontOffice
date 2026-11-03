<?php

namespace Tests\Feature\Display;

use App\Rules\SignatureImage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SignatureImageRuleTest extends TestCase
{
    private const SMALL_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagecreate')) {
            $this->markTestSkipped('Ekstensi GD tidak aktif di PHP CLI.');
        }
    }

    private function passes(string $value): bool
    {
        return Validator::make(['tanda_tangan' => $value], ['tanda_tangan' => [new SignatureImage()]])->passes();
    }

    private function pngBinary(int $width, int $height): string
    {
        $image = imagecreate($width, $height);
        imagecolorallocate($image, 255, 255, 255);

        ob_start();
        imagepng($image);

        return ob_get_clean();
    }

    private function dataUrl(string $binary): string
    {
        return 'data:image/png;base64,'.base64_encode($binary);
    }

    public function test_it_accepts_a_real_png(): void
    {
        $this->assertTrue($this->passes(self::SMALL_PNG));
    }

    public function test_it_accepts_a_canvas_as_large_as_a_4k_screen(): void
    {
        $this->assertTrue($this->passes($this->dataUrl($this->pngBinary(3840, 2160))));
    }

    public function test_it_rejects_the_wrong_prefix(): void
    {
        $this->assertFalse($this->passes(str_replace('image/png', 'image/jpeg', self::SMALL_PNG)));
    }

    public function test_it_rejects_data_that_is_not_base64(): void
    {
        $this->assertFalse($this->passes('data:image/png;base64,bukan-png'));
    }

    public function test_it_rejects_a_png_header_followed_by_arbitrary_bytes(): void
    {
        $this->assertFalse($this->passes($this->dataUrl("\x89PNG\r\n\x1a\n".str_repeat('<?php echo 1; ', 50))));
    }

    public function test_it_rejects_a_png_whose_image_data_is_cut_off(): void
    {
        $binary = $this->pngBinary(300, 100);

        $this->assertFalse($this->passes($this->dataUrl(substr($binary, 0, strlen($binary) - 30))));
    }

    public function test_it_rejects_a_png_with_bytes_appended_after_the_end_of_the_image(): void
    {
        $this->assertFalse($this->passes($this->dataUrl($this->pngBinary(300, 100).'<?php echo 1; ?>')));
    }

    public function test_it_rejects_a_png_whose_image_data_is_corrupted_inside(): void
    {
        $binary = $this->pngBinary(300, 100);

        $this->assertFalse($this->passes($this->dataUrl(substr_replace($binary, str_repeat("\xFF", 12), 60, 12))));
    }

    public function test_it_rejects_a_side_longer_than_the_limit(): void
    {
        $this->assertFalse($this->passes($this->dataUrl($this->pngBinary(6000, 100))));
    }

    public function test_it_rejects_an_image_with_too_many_pixels(): void
    {
        $this->assertFalse($this->passes($this->dataUrl($this->pngBinary(5000, 3000))));
    }
}
