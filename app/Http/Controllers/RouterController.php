<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'name'          => ['required', 'string', 'max:100'],
            'ip_address'    => ['required', 'ip'],
            'api_port'      => ['required', 'integer', 'min:1', 'max:65535'],
            'api_username'  => ['required', 'string', 'max:100'],
            'api_secret'    => ['required', 'string', 'max:255'],
            'radius_secret' => ['nullable', 'string', 'max:255'],
            'location'      => ['nullable', 'string', 'max:200'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $router = Router::create($data);

        // Sync ke tabel nas & restart FreeRADIUS
        $router->syncToNas();
        $this->restartFreeRadius();

        return redirect()
            ->route('routers.show', $router)
            ->with('success', "Router '{$router->name}' berhasil ditambahkan" . ($router->radius_secret ? ' dan didaftarkan ke FreeRADIUS.' : '.'));
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

        // Simpan old IP sebelum update (untuk update nas jika IP berubah)
        $oldIp = $router->ip_address;

        $router->update($data);

        // Jika IP berubah, hapus entry nas lama dulu
        if ($oldIp !== $router->ip_address) {
            DB::table('nas')->where('nasname', $oldIp)->delete();
        }

        // Sync ke tabel nas & restart FreeRADIUS
        $router->syncToNas();
        $this->restartFreeRadius();

        return redirect()
            ->route('routers.show', $router)
            ->with('success', "Router '{$router->name}' berhasil diperbarui.");
    }

    public function destroy(Router $router)
    {
        $name = $router->name;

        // Hapus dari tabel nas & restart FreeRADIUS
        $router->removeFromNas();
        $this->restartFreeRadius();

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

    /**
     * Restart FreeRADIUS agar membaca ulang tabel nas dari SQL (generate_sql_clients).
     * reload (SIGHUP) tidak cukup karena tidak me-reload SQL clients.
     *
     * Requires sudoers:
     *   www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart freeradius
     */
    private function restartFreeRadius(): void
    {
        $output = [];
        $code   = 0;

        exec('sudo /bin/systemctl restart freeradius 2>&1', $output, $code);

        if ($code !== 0) {
            Log::warning('FreeRADIUS restart gagal', [
                'exit_code' => $code,
                'output'    => implode('\n', $output),
            ]);
        }
    }
}
