<?php

namespace App\Http\Controllers;

use App\Models\DisplayDevice;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cookie;

class DisplayController extends Controller
{
    public function pairForm(): View {
        return view('display.pair');
    }

    public function show(): View {
        return view('display.index');
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
    
}
