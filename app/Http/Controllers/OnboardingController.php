<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function show()
    {
        if (AppUser::where('role', 'superuser')->exists()) {
            return redirect()->route('login')->with('info', 'Setup sudah selesai. Silakan login.');
        }
        return view('onboarding.setup');
    }

    public function store(Request $request)
    {
        if (AppUser::where('role', 'superuser')->exists()) {
            return redirect()->route('login');
        }

        $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:app_users,email',
            'password'              => 'required|min:8|confirmed',
        ]);

        AppUser::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => 'superuser',
            'is_active' => true,
        ]);

        return redirect()->route('login')->with('success', 'Super user berhasil dibuat. Silakan login.');
    }
}
