<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'RadiusManager') }} — @yield('title', 'Dashboard')</title>
    <meta name="description" content="RadiusManager — Panel administrasi FreeRADIUS terpusat">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50 overflow-hidden">

<div class="flex h-screen">

    {{-- SIDEBAR --}}
    <aside id="sidebar"
        class="flex flex-col w-64 flex-shrink-0 transition-transform duration-300 ease-in-out
               lg:translate-x-0 -translate-x-full fixed lg:static inset-y-0 z-40"
        style="background: linear-gradient(180deg, #0d1117 0%, #0f1623 100%);">

        <div class="flex items-center gap-3 px-5 h-16 border-b border-white/5 flex-shrink-0">
            <div class="flex items-center justify-center w-9 h-9 rounded-xl flex-shrink-0"
                 style="background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 4px 14px rgba(99,102,241,0.4);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="2"/>
                    <path d="M12 2a10 10 0 0 1 10 10"/><path d="M12 2a10 10 0 0 0-10 10"/>
                    <path d="M12 6a6 6 0 0 1 6 6"/><path d="M12 6a6 6 0 0 0-6 6"/>
                </svg>
            </div>
            <div>
                <span class="text-white font-bold text-sm tracking-wide">RadiusManager</span>
                <p class="text-slate-500 text-[10px] leading-none mt-0.5">Management Panel</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            @php $role = auth('app')->user()?->role; @endphp

            <x-nav-item route="dashboard" icon="home">Dashboard</x-nav-item>

            <p class="section-divider">Voucher</p>
            <x-nav-item route="vouchers.index" icon="ticket">Daftar Voucher</x-nav-item>
            <x-nav-item route="vouchers.create" icon="layers">Generate Batch</x-nav-item>

            <p class="section-divider">Member</p>
            <x-nav-item route="members.index" icon="users">Daftar Member</x-nav-item>
            <x-nav-item route="billing.index" icon="receipt">Billing & Tagihan</x-nav-item>

            <p class="section-divider">Monitoring</p>
            <x-nav-item route="online.index" icon="activity">User Online</x-nav-item>

            @if($role === 'superuser')
            <p class="section-divider">Laporan</p>
            <x-nav-item route="reports.index" icon="bar-chart-2">Laporan Bulanan</x-nav-item>

            <p class="section-divider">Manajemen</p>
            <x-nav-item route="plans.index" icon="package">Paket Internet</x-nav-item>
            <x-nav-item route="routers.index" icon="server">Router / NAS</x-nav-item>
            <x-nav-item route="operators.index" icon="user-check">Operator</x-nav-item>
            <x-nav-item route="settings.index" icon="settings">Pengaturan Sistem</x-nav-item>
            @endif
        </nav>

        <div class="px-3 py-3 border-t border-white/5 flex-shrink-0">
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/5 transition-all duration-150 group">
                <div class="w-8 h-8 rounded-xl flex-shrink-0 flex items-center justify-center font-bold text-xs text-white"
                     style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                    {{ strtoupper(substr(auth('app')->user()?->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-200 truncate">{{ auth('app')->user()?->name }}</p>
                    <p class="text-[10px] text-slate-500 capitalize tracking-wide">{{ auth('app')->user()?->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout"
                        class="text-slate-600 hover:text-red-400 transition-colors p-1 rounded-lg hover:bg-white/5">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div id="sidebar-overlay"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 lg:hidden hidden transition-opacity duration-300"
         onclick="toggleSidebar()"></div>

    {{-- MAIN --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <header class="flex items-center justify-between px-6 h-16 bg-white/80 backdrop-blur-md border-b border-slate-100 flex-shrink-0 sticky top-0 z-20"
                style="box-shadow: 0 1px 0 rgba(0,0,0,0.04);">
            <div class="flex items-center gap-4">
                <button id="hamburger-btn" onclick="toggleSidebar()"
                    class="lg:hidden w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <div>
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight">@yield('title', 'Dashboard')</h1>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @yield('topbar-actions')
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            @if(session('success'))
            <div class="flash-success mb-5 animate-slide-up">
                <svg class="flex-shrink-0 w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="flash-error mb-5 animate-slide-up">
                <svg class="flex-shrink-0 w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                {{ session('error') }}
            </div>
            @endif
            @if(session('warning'))
            <div class="flash-warning mb-5 animate-slide-up">
                <svg class="flex-shrink-0 w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                {{ session('warning') }}
            </div>
            @endif

            <div class="animate-fade-in">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const isOpen  = !sidebar.classList.contains('-translate-x-full');
    if (isOpen) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    } else {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    }
}
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        document.querySelectorAll('.flash-success, .flash-error, .flash-warning').forEach(function (el) {
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(-6px)';
            setTimeout(() => el.remove(), 400);
        });
    }, 4000);
});
</script>
@stack('scripts')
</body>
</html>
