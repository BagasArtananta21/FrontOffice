<?php

namespace App\Http\Controllers;

use App\Models\DisplayDevice;
use App\Models\Bidang;
use App\Models\Kunjungan;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Database\QueryException;
use App\Http\Requests\StoreKunjunganRequest;
use App\Services\KunjunganService;
use App\Rules\SignatureImage;

class DisplayController extends Controller
{
    public function pairForm(): View {
        return view('display.pair');
    }

    public function show(Request $request): View {
        /** @var DisplayDevice $device */
        $device = $request->attributes->get('display_device');
        $waiting = $device->kunjunganMenunggu;

        return view('display.index', [
            'opd' => $device->opd,
            'bidang' => Bidang::active()->orderBy('nama_bidang')->get(['id', 'nama_bidang']),
            'initialShowForm' => $device->tampilkan_form,
            'waitingKey' => $device->waitingKey(),
            'summary' => $waiting ? $this->visitSummary($waiting) : null,
        ]);
    }

    public function status(Request $request): JsonResponse {
        /** @var DisplayDevice $device */
        $device = $request->attributes->get('display_device');

        if ($device->terakhir_aktif === null || $device->terakhir_aktif->lessThan(now()->subSeconds(10))){
            $device->timestamps = false;
            $device->terakhir_aktif = now();
            $device->saveQuietly();
        }

        return response()->json([
            'status' => 'ok',
            'tampilkan_form' => $device->tampilkan_form,
            'waiting_key' => $device->waitingKey(),
        ]);
    }

    public function pair(Request $request): RedirectResponse {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);
    
        if (!DisplayDevice::findActiveByToken($validated['token'])) {
            Log::warning('Pairing Display Gagal', ['ip' => $request->ip()]);
            throw ValidationException::withMessages([
                'token' => 'Token tidak valid.',
            ]);
        }

        return redirect()->route('display.show')
            ->withCookie(cookie()->forever(DisplayDevice::COOKIE, $validated['token']));
    }

    public function unpair(): RedirectResponse {
        Cookie::expire(DisplayDevice::COOKIE);
        return redirect()->route('display.pair');
    }

    public function storeVisit(StoreKunjunganRequest $request, KunjunganService $kunjunganService): RedirectResponse
    {
        /** @var DisplayDevice $device */
        $device = $request->attributes->get('display_device');

        try {
            $kunjunganService->recordFromDisplay($request->validated(), $device->ditampilkan_oleh);
        } catch (QueryException $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['form' => 'Terjadi kesalahan. Silakan coba lagi atau hubungi petugas.']);
        }

        $device->update(['tampilkan_form' => false]);

        return redirect()->route('display.confirmation');
    }

    public function storeSignature(Request $request, KunjunganService $kunjunganService): RedirectResponse
    {
        $validated = $request->validate(
            ['tanda_tangan' => ['required', 'string', 'max:500000', new SignatureImage()]],
            ['tanda_tangan.required' => 'Tanda tangan wajib diisi.'],
        );

        /** @var DisplayDevice $device */
        $device = $request->attributes->get('display_device');

        try {
            $kunjungan = $kunjunganService->recordSignature($device, $validated['tanda_tangan']);
        } catch (QueryException $e) {
            report($e);

            return back()->withErrors(['form' => 'Terjadi kesalahan. Silakan coba lagi atau hubungi petugas.']);
        }

        if (! $kunjungan) {
            return redirect()->route('display.show');
        }

        return redirect()->route('display.confirmation');
    }

    private function visitSummary(Kunjungan $kunjungan): array
    {
        return array_filter([
            'Nama' => $kunjungan->nama_tamu,
            'Instansi' => $kunjungan->instansi_asal,
            'Bidang yang dituju' => $kunjungan->bidang?->nama_bidang,
            'Pegawai yang dituju' => $kunjungan->pegawai_tujuan,
            'Keperluan' => $kunjungan->keperluan,
        ], fn ($value) => filled($value));
    }

    public function confirmation(): View {
        return view('display.confirmation');
    }
    
}
