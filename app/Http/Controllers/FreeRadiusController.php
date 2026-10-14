<?php

namespace App\Http\Controllers;

use App\Jobs\Radius\RunConfigurationJob;
use App\Services\Radius\FreeRadiusEnvironment;
use App\Services\Radius\FreeRadiusHealthChecker;
use Illuminate\Support\Facades\Gate;

class FreeRadiusController extends Controller
{
    public function index(FreeRadiusEnvironment $environment, FreeRadiusHealthChecker $health)
    {
        Gate::authorize('superuser-only');
        return view('radius.index', ['environment' => $environment->inspect(), 'health' => $health->check()]);
    }

    public function reconcile()
    {
        Gate::authorize('superuser-only');
        RunConfigurationJob::dispatch('CONFIG_UPDATE');
        return back()->with('success', 'Reconfiguration FreeRADIUS dimasukkan ke antrean.');
    }
}
