@extends('layouts.guest')
@section('title', 'Login')

@section('content')
<div class="min-h-screen flex">

    {{-- Left panel — branding --}}
    <div class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 bg-gradient-to-br from-slate-900 via-slate-800 to-blue-950 relative overflow-hidden">

        {{-- Background decoration --}}
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-blue-600/10 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-20 w-80 h-80 rounded-full bg-blue-500/10 blur-3xl"></div>
        </div>

        {{-- Logo --}}
        <div class="relative flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-600">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="2"/>
                    <path d="M12 2a10 10 0 0 1 10 10"/>
                    <path d="M12 2a10 10 0 0 0-10 10"/>
                    <path d="M12 6a6 6 0 0 1 6 6"/>
                    <path d="M12 6a6 6 0 0 0-6 6"/>
                </svg>
            </div>
            <span class="text-white font-semibold text-lg">RadiusManager</span>
        </div>

        {{-- Tagline --}}
        <div class="relative">
            <h2 class="text-3xl font-bold text-white leading-snug mb-4">
                Kelola jaringan<br>
                <span class="text-blue-400">lebih efisien.</span>
            </h2>
            <p class="text-slate-400 text-sm leading-relaxed max-w-xs">
                Platform manajemen FreeRADIUS untuk ISP dan hotspot — voucher, member, billing, dan monitoring dalam satu dasbor.
            </p>

            {{-- Features --}}
            <div class="mt-8 space-y-3">
                @foreach([
                    ['icon' => 'ticket',    'text' => 'Generate & cetak voucher massal'],
                    ['icon' => 'users',     'text' => 'Manajemen member berlangganan'],
                    ['icon' => 'bar-chart', 'text' => 'Laporan trafik & pendapatan'],
                ] as $f)
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-lg bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                        @if($f['icon'] === 'ticket')
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2z"/></svg>
                        @elseif($f['icon'] === 'users')
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        @else
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        @endif
                    </div>
                    <span class="text-slate-300 text-sm">{{ $f['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <p class="relative text-slate-600 text-xs">&copy; {{ date('Y') }} RadiusManager</p>
    </div>

    {{-- Right panel — login form --}}
    <div class="flex-1 flex items-center justify-center p-8 bg-white">
        <div class="w-full max-w-sm">

            {{-- Mobile logo --}}
            <div class="lg:hidden flex items-center gap-2 mb-8">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M12 2a10 10 0 0 1 10 10"/>
                        <path d="M12 2a10 10 0 0 0-10 10"/>
                    </svg>
                </div>
                <span class="font-semibold text-slate-800">RadiusManager</span>
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mb-1">Selamat datang</h1>
            <p class="text-sm text-slate-500 mb-8">Masuk ke akun Anda untuk melanjutkan</p>

            {{-- Error global --}}
            @if($errors->any())
            <div class="mb-5 flex items-start gap-3 px-4 py-3 rounded-lg bg-red-50 border border-red-200">
                <svg class="flex-shrink-0 mt-0.5" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p class="text-sm text-red-700">{{ $errors->first() }}</p>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="admin@radius.local"
                        autocomplete="email"
                        autofocus
                        class="form-input @error('email') border-red-400 focus:ring-red-500 @enderror"
                    >
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="form-label">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            class="form-input pr-10 @error('password') border-red-400 @enderror"
                        >
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember me --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="remember" class="text-sm text-slate-600">Ingat saya</label>
                </div>

                <button type="submit" class="btn-primary w-full">
                    Masuk
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-slate-400">
                RadiusManager &mdash; ISP & Hotspot Management
            </p>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
@endsection
