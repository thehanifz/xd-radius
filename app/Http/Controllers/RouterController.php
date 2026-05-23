<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;

class RouterController extends Controller
{
    public function index()
    {
        $routers = Router::withoutTrashed()->latest()->paginate(20);
        return view('routers.index', compact('routers'));
    }

    public function create()
    {
        return view('routers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'ip_address'   => ['required', 'ip'],
            'api_port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'api_username' => ['required', 'string', 'max:100'],
            'api_secret'   => ['required', 'string', 'max:255'],
            'location'     => ['nullable', 'string', 'max:200'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $router = Router::create($data);

        return redirect()
            ->route('routers.show', $router)
            ->with('success', "Router '{$router->name}' berhasil ditambahkan.");
    }

    public function show(Router $router)
    {
        return view('routers.show', compact('router'));
    }

    public function edit(Router $router)
    {
        return view('routers.edit', compact('router'));
    }

    public function update(Request $request, Router $router)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'ip_address'   => ['required', 'ip'],
            'api_port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'api_username' => ['required', 'string', 'max:100'],
            'api_secret'   => ['nullable', 'string', 'max:255'],
            'location'     => ['nullable', 'string', 'max:200'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', false);

        // Kosong = tidak ubah secret
        if (empty($data['api_secret'])) {
            unset($data['api_secret']);
        }

        $router->update($data);

        return redirect()
            ->route('routers.show', $router)
            ->with('success', "Router '{$router->name}' berhasil diperbarui.");
    }

    public function destroy(Router $router)
    {
        $name = $router->name;
        $router->delete();

        return redirect()
            ->route('routers.index')
            ->with('success', "Router '{$name}' berhasil dihapus.");
    }

    public function toggleActive(Router $router)
    {
        $router->update(['is_active' => ! $router->is_active]);
        $label = $router->fresh()->status_label;

        return back()->with('success', "Status router '{$router->name}' diubah ke {$label}.");
    }
}
