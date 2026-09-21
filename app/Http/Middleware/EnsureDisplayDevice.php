<?php

namespace App\Http\Middleware;

use App\Models\DisplayDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureDisplayDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $device = DisplayDevice::findActiveByToken($request->cookie(DisplayDevice::COOKIE));

        if (! $device) {
            Log::warning('Akses Display Ditolak', ['ip' => $request->ip()]);
            abort(403, 'Perangkat ini belum didaftarkan sebagai Display Device.');
        }

        app()->instance('current_opd_id', $device->opd_id);
        $request->attributes->set('display_device', $device);

        Cookie::queue(cookie()->forever(DisplayDevice::COOKIE, $request->cookie(DisplayDevice::COOKIE)));
        return $next($request);
    }
}
