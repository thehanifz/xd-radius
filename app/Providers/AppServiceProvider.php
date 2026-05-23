<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Gate untuk superuser-only
        Gate::define('superuser-only', function ($user) {
            return $user->isSuperUser();
        });
    }
}
