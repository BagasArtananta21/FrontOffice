<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SystemSettingService
{
    public const SLIDE_MAX_COUNT = 'slide_max_count';
    public const SLIDE_MAX_SIZE_MB = 'slide_max_size_mb';
    public const SLIDE_DURATION_SECONDS = 'slide_duration_seconds';
    public const IDLE_SCREENSAVER_MINUTES = 'idle_screensaver_minutes';
    public const SIGNATURE_TIMEOUT_MINUTES = 'signature_timeout_minutes';
    public const CONFIRMATION_SECONDS = 'confirmation_seconds';

    public const DEFINITIONS = [
        self::SLIDE_MAX_COUNT => ['default' => 3, 'min' => 1, 'max' => 10],
        self::SLIDE_MAX_SIZE_MB => ['default' => 2, 'min' => 1, 'max' => 5],
        self::SLIDE_DURATION_SECONDS => ['default' => 8, 'min' => 3, 'max' => 60],
        self::IDLE_SCREENSAVER_MINUTES => ['default' => 5, 'min' => 1, 'max' => 30],
        self::SIGNATURE_TIMEOUT_MINUTES => ['default' => 5, 'min' => 1, 'max' => 15],
        self::CONFIRMATION_SECONDS => ['default' => 5, 'min' => 3, 'max' => 30],
    ];

    /** @var array<string, string>|null */
    private ?array $stored = null;

    public function get(string $key): int
    {
        $definition = $this->definition($key);
        $this->stored ??= Pengaturan::query()->pluck('nilai', 'kunci')->all();

        return isset($this->stored[$key]) ? (int) $this->stored[$key] : $definition['default'];
    }

    /** @return array<string, int> */
    public function all(): array
    {
        $values = [];

        foreach (array_keys(self::DEFINITIONS) as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            $definition = $this->definition($key);

            if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
                throw new InvalidArgumentException("Nilai pengaturan [{$key}] harus bilangan bulat.");
            }

            if ((int) $value < $definition['min'] || (int) $value > $definition['max']) {
                throw new InvalidArgumentException("Nilai pengaturan [{$key}] harus antara {$definition['min']} dan {$definition['max']}.");
            }
        }

        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                Pengaturan::query()->updateOrCreate(['kunci' => $key], ['nilai' => (string) (int) $value]);
            }
        });

        $this->stored = null;
    }

    private function definition(string $key): array
    {
        return self::DEFINITIONS[$key]
            ?? throw new InvalidArgumentException("Pengaturan [{$key}] tidak dikenal.");
    }
}