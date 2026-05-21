<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperator
{
    /**
     * Middleware ini mengizinkan SEMUA user yang sudah login
     * (baik superuser maupun operator), karena superuser
     * memiliki akses ke semua fitur operator.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('app')->user();

        if (! $user || (! $user->isSuperUser() && ! $user->isOperator())) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden. Please login.'], 403);
            }

            abort(403, 'Forbidden. Please login.');
        }

        return $next($request);
    }
}
