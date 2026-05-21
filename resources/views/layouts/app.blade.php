<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'RadiusManager') }} — @yield('title', 'Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside id="sidebar" class="flex flex-col w-64 bg-sidebar text-sidebar-text flex-shrink-0 transition-transform duration-200 lg:translate-x-0 -translate-x-full fixed lg:static inset-y-0 z-40">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-sidebar-border">
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="2"/>
                    <path d="M12 2a10 10 0 0 1 10 10"/>
                    <path d="M12 2a10 10 0 0 0-10 10"/>
                    <path d="M12 6a6 6 0 0 1 6 6"/>
                    <path d="M12 6a6 6 0 0 0-6 6"/>
                </svg>
            </div>
            <span class="text-white font-semibold text-sm tracking-wide">RadiusManager</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

            @php $role = auth('app')->user()?->role; @endphp

            {{-- Dashboard --}}
            <x-nav-item route="dashboard" icon="home">Dashboard</x-nav-item>

            {{-- Voucher --}}
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Voucher</p>
            </div>
            <x-nav-item route="vouchers.index" icon="ticket">Daftar Voucher</x-nav-item>
            <x-nav-item route="vouchers.create" icon="layers">Generate Batch</x-nav-item>

            {{-- Member --}}
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Member</p>
            </div>
            <x-nav-item route="members.index" icon="users">Daftar Member</x-nav-item>

            {{-- Superuser only --}}
            @if($role === 'superuser')
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Manajemen</p>
            </div>
            <x-nav-item route="plans.index" icon="package">Paket Internet</x-nav-item>
            @endif

        </nav>

        {{-- User info --}}
        <div class="px-3 py-3 border-t border-sidebar-border">
            <div class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-sidebar-hover transition-colors">
                <div class="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth('app')->user()?->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-slate-200 truncate">{{ auth('app')->user()?->name }}</p>
                    <p class="text-xs text-slate-500 capitalize">{{ auth('app')->user()?->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="text-slate-500 hover:text-red-400 transition-colors">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 lg:hidden hidden" onclick="toggleSidebar()"></div>

    {{-- MAIN CONTENT --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Topbar --}}
        <header class="flex items-center justify-between px-6 py-4 bg-white border-b border-slate-200 flex-shrink-0">
            <div class="flex items-center gap-4">
                {{-- Hamburger mobile --}}
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-slate-700">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <h1 class="text-base font-semibold text-slate-800">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-3">
                @yield('topbar-actions')
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-6">
            {{-- Flash messages --}}
            @if(session('success'))
            <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                {{ session('error') }}
            </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>
@stack('scripts')
</body>
</html>
