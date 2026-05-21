<?php

use App\Http\Middleware\EnsureOperator;
use App\Http\Middleware\EnsureSuperUser;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust Cloudflare & reverse proxy
        $middleware->trustProxies(at: '*');

        // Global middleware untuk semua request yang sudah login
        $middleware->appendToGroup('web', EnsureUserIsActive::class);

        // Named alias untuk route middleware
        $middleware->alias([
            'superuser' => EnsureSuperUser::class,
            'operator'  => EnsureOperator::class,
            'active'    => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
