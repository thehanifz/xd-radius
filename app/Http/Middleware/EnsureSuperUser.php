<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('app')->user();

        if (! $user || ! $user->isSuperUser()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden. Super User access required.'], 403);
            }

            abort(403, 'Forbidden. Super User access required.');
        }

        return $next($request);
    }
}
