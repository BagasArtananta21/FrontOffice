<?php

namespace App\Http\Middleware;

use App\Models\DisplayDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfDisplayDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (DisplayDevice::findActiveByToken($request->cookie(DisplayDevice::COOKIE))) {
            return redirect()->route('display.show');
        }

        return $next($request);
    }

}
