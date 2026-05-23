<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperuserOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('app')->user();
        if (! $user || ! $user->isSuperUser()) {
            abort(403, 'Akses ditolak.');
        }
        return $next($request);
    }
}
