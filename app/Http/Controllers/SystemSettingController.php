<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SystemSettingController extends Controller
{
    public function index()
    {
        Gate::authorize('superuser-only');

        $settings = SystemSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        Gate::authorize('superuser-only');

        $request->validate([
            'settings'              => 'required|array',
            'settings.app_name'     => 'nullable|string|max:100',
            'settings.ssid_name'    => 'nullable|string|max:100',
            'settings.stale_threshold_minutes' => 'nullable|integer|min:5|max:1440',
            'settings.invoice_days_before'     => 'nullable|integer|min:1|max:30',
            'settings.overdue_isolate_auto'    => 'nullable',
        ]);

        foreach ($request->settings as $key => $value) {
            SystemSetting::set($key, $value ?? '');
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
