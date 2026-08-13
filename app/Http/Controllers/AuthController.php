<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('app')->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $throttleKey = mb_strtolower($request->input('email')) . '|' . $request->ip();

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (Auth::guard('app')->attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            activity()
                ->causedBy(Auth::guard('app')->user())
                ->log('Login berhasil');

            return redirect()->intended(route('dashboard'));
        }

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        activity()
            ->causedBy(Auth::guard('app')->user())
            ->log('Logout');

        Auth::guard('app')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
