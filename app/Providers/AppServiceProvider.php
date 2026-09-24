<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentGateway;
use App\Services\Payments\Doku\DokuClient;
use App\Services\Payments\Doku\DokuPaymentGateway;
use App\Services\Payments\Doku\DokuSettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DokuClient::class, fn () => DokuClient::fromConfig());
        $this->app->bind(PaymentGateway::class, DokuPaymentGateway::class);
    }

    public function boot(DokuSettingsService $dokuSettings): void
    {
        $dokuSettings->applyToConfig();

        // Gate untuk superuser-only
        Gate::define('superuser-only', function ($user) {
            return $user->isSuperUser();
        });
    }
}
