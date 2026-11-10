<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Siswa aktif yang belum menyetujui pakta integritas diarahkan ke halaman pakta (PRD 9.4). */
class EnsureIntegrityPact
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->member?->needsIntegrityPact() && ! $request->routeIs('pact.*')) {
            return redirect()->route('pact.show');
        }

        return $next($request);
    }
}
