<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('password.*', 'logout')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Anda wajib mengganti password awal terlebih dahulu.'], 403);
            }

            return redirect()->route('password.change')->with('status', 'Anda wajib mengganti password awal terlebih dahulu.');
        }

        return $next($request);
    }
}
