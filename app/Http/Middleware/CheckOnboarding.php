<?php

namespace App\Http\Middleware;

use App\Models\AppUser;
use Closure;
use Illuminate\Http\Request;

class CheckOnboarding
{
    public function handle(Request $request, Closure $next)
    {
        // Kalau belum ada superuser, redirect ke onboarding
        if (! AppUser::where('role', 'superuser')->exists()) {
            if (! $request->routeIs('onboarding.*')) {
                return redirect()->route('onboarding.show');
            }
        }

        return $next($request);
    }
}
