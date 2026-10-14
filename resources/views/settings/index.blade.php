@extends('layouts.app')
@section('title', 'Pengaturan')

@section('content')
<div class="space-y-5">
    <header class="max-w-3xl">
        <p class="text-xs font-semibold uppercase tracking-[.14em] text-indigo-400">Sistem</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">Pengaturan</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Pilih bagian yang ingin Anda kelola.</p>
    </header>

    <section class="grid gap-4 md:grid-cols-2" aria-label="Menu pengaturan">
        <a href="{{ route('operators.index') }}" class="settings-hub-card group">
            <span class="settings-hub-icon text-cyan-300 bg-cyan-400/10" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <span class="settings-hub-copy">
                <strong>Operator</strong>
                <span>Kelola akun operator dan hak akses ke panel.</span>
            </span>
            <svg class="settings-hub-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
        </a>

        <a href="{{ route('settings.doku.index') }}" class="settings-hub-card group">
            <span class="settings-hub-icon text-emerald-300 bg-emerald-400/10" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h3"/></svg>
            </span>
            <span class="settings-hub-copy">
                <strong>Pembayaran DOKU</strong>
                <span>Atur koneksi gateway, kredensial, dan kanal Virtual Account.</span>
            </span>
            <svg class="settings-hub-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    </section>
</div>
@endsection
