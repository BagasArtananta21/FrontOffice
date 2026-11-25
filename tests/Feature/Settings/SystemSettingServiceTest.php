<?php

namespace Tests\Feature\Settings;

use App\Services\SystemSettingService as Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SystemSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function settings(): Settings
    {
        return app(Settings::class);
    }

    public function test_defaults_match_the_values_used_before_settings_existed(): void
    {
        $this->assertSame([
            Settings::SLIDE_MAX_COUNT => 3,
            Settings::SLIDE_MAX_SIZE_MB => 2,
            Settings::SLIDE_DURATION_SECONDS => 8,
            Settings::IDLE_SCREENSAVER_MINUTES => 5,
            Settings::SIGNATURE_TIMEOUT_MINUTES => 5,
            Settings::CONFIRMATION_SECONDS => 5,
        ], $this->settings()->all());
    }

    public function test_an_update_is_stored_and_read_back(): void
    {
        $this->settings()->update([Settings::SLIDE_MAX_COUNT => 5]);

        $this->assertSame(5, $this->settings()->get(Settings::SLIDE_MAX_COUNT));
        $this->assertDatabaseHas('pengaturan', ['kunci' => Settings::SLIDE_MAX_COUNT, 'nilai' => '5']);
    }

    public function test_updating_the_same_key_twice_keeps_one_row(): void
    {
        $this->settings()->update([Settings::SLIDE_MAX_COUNT => 4]);
        $this->settings()->update([Settings::SLIDE_MAX_COUNT => 6]);

        $this->assertDatabaseCount('pengaturan', 1);
        $this->assertSame(6, $this->settings()->get(Settings::SLIDE_MAX_COUNT));
    }

    public static function invalidValues(): array
    {
        return [
            'below minimum' => [Settings::SLIDE_MAX_COUNT, 0],
            'above maximum' => [Settings::SLIDE_MAX_COUNT, 11],
            'decimal' => [Settings::IDLE_SCREENSAVER_MINUTES, '2.5'],
            'text' => [Settings::IDLE_SCREENSAVER_MINUTES, 'lima'],
            'unknown key' => ['tamu_maksimal', 10],
        ];
    }

    #[DataProvider('invalidValues')]
    public function test_an_invalid_value_rejects_the_whole_update(string $key, mixed $value): void
    {
        try {
            $this->settings()->update([Settings::CONFIRMATION_SECONDS => 10, $key => $value]);
            $this->fail('InvalidArgumentException tidak dilempar.');
        } catch (InvalidArgumentException) {
        }

        $this->assertDatabaseCount('pengaturan', 0);
    }
}