<?php

namespace App\Http\Controllers;

use App\Jobs\Radius\RunSetupJob;
use App\Jobs\Radius\RunConfigurationJob;
use App\Services\Radius\FreeRadiusEnvironment;
use App\Services\Radius\FreeRadiusHealthChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FreeRadiusController extends Controller
{
    public function index(FreeRadiusEnvironment $environment, FreeRadiusHealthChecker $health)
    {
        Gate::authorize('superuser-only');
        return view('radius.index', ['environment' => $environment->inspect(), 'health' => $health->check()]);
    }

    public function setup(Request $request)
    {
        Gate::authorize('superuser-only');
        RunSetupJob::dispatch();
        return back()->with('success', 'Setup FreeRADIUS dimasukkan ke antrean. Periksa kembali halaman ini setelah worker selesai.');
    }

    public function reconcile(Request $request)
    {
        Gate::authorize('superuser-only');
        RunConfigurationJob::dispatch('CONFIG_UPDATE');
        return back()->with('success', 'Reconfiguration FreeRADIUS dimasukkan ke antrean.');
    }
}
