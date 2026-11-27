<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Slide rows for tests, inserted directly so a test can set up another OPD's
 * data without logging in as that OPD. Expects BuildsFixtures (newId()) and a
 * faked "public" disk.
 */
trait BuildsSlides
{
    protected function insertSlide(string $opdId, int $urutan, ?string $path = null): string
    {
        $id = $this->newId();
        $path ??= "slide-idle/{$id}.png";

        Storage::disk('public')->put($path, 'isi-gambar');

        DB::table('slide_idle')->insert([
            'id' => $id,
            'opd_id' => $opdId,
            'gambar' => $path,
            'urutan' => $urutan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    protected function slideOrder(string $slideId): int
    {
        return (int) DB::table('slide_idle')->where('id', $slideId)->value('urutan');
    }

    /**
     * @return array<int, string>
     */
    protected function storedSlideFiles(): array
    {
        return Storage::disk('public')->allFiles('slide-idle');
    }
}
