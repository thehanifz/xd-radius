<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\RouterConnectionService;
use App\Jobs\Radius\RunConfigurationJob;

class RouterController extends Controller
{
    public function index()
    {
        Gate::authorize('superuser-only');
        $routers = Router::withoutTrashed()->latest()->paginate(20);
        return view('routers.index', compact('routers'));
    }

    public function create()
    {
        Gate::authorize('superuser-only');
        return view('routers.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('superuser-only');
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'ip_address'    => ['required', 'ip'],
            'api_port'      => ['required', 'integer', 'min:1', 'max:65535'],
            'api_username'  => ['required', 'string', 'max:100'],
            'api_secret'    => ['required', 'string', 'max:255'],
            'radius_secret' => ['required', 'string', 'max:255'],
            'location'      => ['nullable', 'string', 'max:200'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['radius_enabled'] = true;

        $router = Router::create($data);

        $router->updateQuietly(['radius_sync_status' => 'pending', 'radius_last_sync_error' => null]);
        RunConfigurationJob::dispatch('NAS_SYNC', $router->id);

        return redirect()
            ->route('routers.show', $router)
            ->with('success', "Router '{$router->name}' berhasil ditambahkan" . ($router->radius_secret ? ' dan didaftarkan ke FreeRADIUS.' : '.'));
    }

    public function show(Router $router)
    {
        Gate::authorize('superuser-only');
        return view('routers.show', compact('router'));
    }

    public function testConnection(Router $router, RouterConnectionService $connectionService)
    {
        Gate::authorize('superuser-only');

        $result = $connectionService->test($router);

        if (! $result['ok']) {
            return back()->with('error', "Koneksi ke {$router->name} gagal: {$result['message']}");
        }

        return back()->with('success', "Koneksi {$router->name} berhasil. RouterOS {$result['version']}");
    }

    public function edit(Router $router)
    {
        Gate::authorize('superuser-only');
        return view('routers.edit', compact('router'));
    }

    public function update(Request $request, Router $router)
    {
        Gate::authorize('superuser-only');
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'ip_address'    => ['required', 'ip'],
            'api_port'      => ['required', 'integer', 'min:1', 'max:65535'],
            'api_username'  => ['required', 'string', 'max:100'],
            'api_secret'    => ['nullable', 'string', 'max:255'],
            'radius_secret' => ['nullable', 'string', 'max:255'],
            'location'      => ['nullable', 'string', 'max:200'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', false);

        // Kosong = tidak ubah api_secret
        if (empty($data['api_secret'])) {
            unset($data['api_secret']);
        }

        $router->update($data);

        $router->updateQuietly(['radius_sync_status' => 'pending', 'radius_last_sync_error' => null]);
        RunConfigurationJob::dispatch('NAS_SYNC', $router->id);

        return redirect()
            ->route('routers.show', $router)
            ->with('success', "Router '{$router->name}' berhasil diperbarui.");
    }

    public function destroy(Router $router)
    {
        Gate::authorize('superuser-only');
        $name = $router->name;

        $router->delete();
        $router->updateQuietly(['radius_sync_status' => 'pending', 'radius_last_sync_error' => null]);
        RunConfigurationJob::dispatch('NAS_SYNC', $router->id);

        return redirect()
            ->route('routers.index')
            ->with('success', "Router '{$name}' berhasil dihapus.");
    }

    public function toggleOperational(Router $router)
    {
        Gate::authorize('superuser-only');

        $enabled = ! $router->is_active;
        $router->update([
            'is_active'      => $enabled,
            'radius_enabled' => $enabled,
        ]);
        $router = $router->fresh();

        $router->updateQuietly(['radius_sync_status' => 'pending', 'radius_last_sync_error' => null]);
        RunConfigurationJob::dispatch('NAS_SYNC', $router->id);

        return back()->with(
            'success',
            "Router '{$router->name}' dan FreeRADIUS " . ($enabled ? 'diaktifkan.' : 'dinonaktifkan.')
        );
    }

}
