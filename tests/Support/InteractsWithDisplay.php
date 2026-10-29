<?php

namespace Tests\Support;

use App\Models\DisplayDevice;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * One OPD, one officer and one paired display device for HTTP and service tests.
 * Call setUpDisplay() from setUp().
 */
trait InteractsWithDisplay
{
    use BuildsFixtures;

    protected const DISPLAY_TOKEN = 'token-uji-perangkat-display';

    protected const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected Opd $opd;

    protected User $officer;

    protected string $deviceId;

    protected function setUpDisplay(): void
    {
        Storage::fake('local');

        $this->opd = $this->makeOpd('UJI');
        $this->officer = $this->makeUser($this->opd);
        $this->deviceId = $this->insertDisplay($this->opd->id, self::DISPLAY_TOKEN, [
            'ditampilkan_oleh' => $this->officer->id,
        ]);
    }

    protected function updateDevice(array $changes): void
    {
        DB::table('display_devices')->where('id', $this->deviceId)->update($changes);
    }

    /**
     * @return array<string, mixed>
     */
    protected function deviceRow(): array
    {
        return $this->rawRow('display_devices', $this->deviceId);
    }

    protected function showForm(): void
    {
        $this->updateDevice(['tampilkan_form' => true]);
    }

    protected function makeWaitingVisit(array $overrides = []): string
    {
        $visitId = $this->insertVisit($this->opd->id, now()->format('Y-m-d H:i:s'), [
            'nama_tamu' => 'Tamu Menunggu',
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);

        $this->updateDevice(['kunjungan_menunggu_id' => $visitId]);

        return $visitId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function visitPayload(array $overrides = []): array
    {
        return [
            'nama_tamu' => 'Budi Santoso',
            'jenis_kelamin' => 'laki_laki',
            'keperluan' => 'Konsultasi',
            'tanda_tangan' => self::PNG,
            ...$overrides,
        ];
    }

    protected function asDisplay(?string $token = self::DISPLAY_TOKEN): static
    {
        return $token === null ? $this : $this->withCookie(DisplayDevice::COOKIE, $token);
    }

    protected function submitVisit(array $overrides = [], ?string $token = self::DISPLAY_TOKEN): TestResponse
    {
        return $this->asDisplay($token)->post(route('display.kunjungan.store'), $this->visitPayload($overrides));
    }

    protected function submitSignature(array $payload = [], ?string $token = self::DISPLAY_TOKEN): TestResponse
    {
        return $this->asDisplay($token)->post(route('display.signature.store'), [
            'tanda_tangan' => self::PNG,
            ...$payload,
        ]);
    }
}
