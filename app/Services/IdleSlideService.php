<?php

namespace App\Services;

use App\Exceptions\SlideStorageException;
use App\Models\Opd;
use App\Models\SlideIdle;
use App\Services\SystemSettingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IdleSlideService
{
    public function __construct(private SystemSettingService $settings) {}

    public function add(UploadedFile $file): SlideIdle
    {
        $opdId = SlideIdle::currentOpdId()
            ?? throw new AuthorizationException('Konteks OPD wajib tersedia.');

        $extension = match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw new InvalidArgumentException('Slide harus berupa gambar PNG atau JPEG.'),
        };

        $maxMb = $this->settings->get(SystemSettingService::SLIDE_MAX_SIZE_MB);

        if ($file->getSize() > $maxMb * 1024 * 1024) {
            throw new InvalidArgumentException("Ukuran slide maksimal {$maxMb} MB.");
        }

        $path = SlideIdle::DIRECTORY.'/'.Str::uuid().'.'.$extension;

        return DB::transaction(function () use ($file, $opdId, $path) {
            Opd::query()->whereKey($opdId)->lockForUpdate()->firstOrFail();

            $max = $this->settings->get(SystemSettingService::SLIDE_MAX_COUNT);

            if (SlideIdle::query()->count() >= $max) {
                throw new InvalidArgumentException("Jumlah slide sudah mencapai batas ({$max}).");
            }

            $slide = new SlideIdle();
            $slide->forceFill([
                'gambar' => $path,
                'urutan' => (int) SlideIdle::query()->max('urutan') + 1,
            ])->save();

            $stored = Storage::disk(SlideIdle::DISK)->putFileAs(SlideIdle::DIRECTORY, $file, basename($path));

            if ($stored === false) {
                throw new SlideStorageException("Slide gagal ditulis ke disk [{$path}].");
            }

            return $slide;
        });
    }

    public function remove(SlideIdle $slide): void
    {
        DB::transaction(fn () => $slide->delete());

        if (! Storage::disk(SlideIdle::DISK)->delete($slide->gambar)) {
            report(new SlideStorageException("Berkas slide gagal dihapus [{$slide->gambar}]."));
        }
    }
}