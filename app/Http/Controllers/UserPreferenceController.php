<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    /**
     * Simpan preferensi user ke database.
     * POST /user/preferences
     */
    public function store(Request $request)
    {
        $request->validate([
            'key'   => ['required', 'string', 'max:100'],
            'value' => ['required'],
        ]);

        $user = auth('app')->user();

        $user->preferences()->updateOrCreate(
            ['key'   => $request->key],
            ['value' => $request->value]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Ambil semua preferensi user (untuk JS init).
     * GET /user/preferences
     */
    public function index()
    {
        $user  = auth('app')->user();
        $prefs = $user->preferences()->pluck('value', 'key');
        return response()->json($prefs);
    }

    /**
     * Reset satu preferensi.
     * DELETE /user/preferences/{key}
     */
    public function destroy(Request $request, string $key)
    {
        $user = auth('app')->user();
        $user->preferences()->where('key', $key)->delete();
        return response()->json(['ok' => true]);
    }
}
