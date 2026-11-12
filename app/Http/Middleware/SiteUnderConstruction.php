<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

/**
 * Mode "sedang dibangun": pengunjung situs publik melihat halaman under construction (503),
 * pengguna yang sudah login tetap melihat situs untuk meninjau isinya. Login & dashboard tidak terpengaruh.
 */
class SiteUnderConstruction
{
    public function handle(Request $request, Closure $next)
    {
        $site = Setting::current();
        if (! $site->maintenance_mode || $request->user()) {
            return $next($request);
        }

        return response()->view('site.maintenance', ['site' => $site], 503)
            ->header('Retry-After', $site->maintenance_until ? (int) max(3600, now()->diffInSeconds($site->maintenance_until->endOfDay(), false)) : 86400);
    }
}
