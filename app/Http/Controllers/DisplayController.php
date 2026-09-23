<?php

namespace App\Http\Controllers;

use App\Models\DisplayDevice;
use App\Models\Bidang;
use App\Models\Pegawai;
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

class DisplayController extends Controller
{
    public function pairForm(): View {
        return view('display.pair');
    }

    public function show(Request $request): View {
        /** @var DisplayDevice $device */
        $device = $request->attributes->get('display_device');

        return view('display.index', [
            'bidang' => Bidang::active()->orderBy('nama_bidang')->get(['id', 'nama_bidang']),
            'pegawai' => Pegawai::active()->orderBy('nama_pegawai')->get(['id', 'nama_pegawai']),
            'initialShowForm' => $device->tampilkan_form,
        ]);
    }

    public function status(Request $request): JsonResponse {
        /** @var DisplayDevice $device */
        $device = $request->attributes->get('display_device');

        $device->timestamps = false;
        $device->terakhir_aktif = now();
        $device->saveQuietly();

        return response()->json([
            'status' => 'ok',
            'tampilkan_form' => $device->tampilkan_form,
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
            $kunjunganService->record($request->validated(), Kunjungan::SUMBER_DISPLAY);
        } catch (QueryException $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['form' => 'Terjadi kesalahan. Silakan coba lagi atau hubungi petugas.']);
        }

        $device->update(['tampilkan_form' => false]);

        return redirect()->route('display.confirmation');
    }

    public function confirmation(): View {
        return view('display.confirmation');
    }
    
}
