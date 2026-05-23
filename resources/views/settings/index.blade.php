@extends('layouts.app')
@section('title', 'Pengaturan Sistem')

@section('content')
<div class="max-w-2xl">
<form method="POST" action="{{ route('settings.update') }}">
    @csrf @method('PUT')

    {{-- General --}}
    <div class="card mb-6">
        <div class="card-header">
            <h2 class="card-title">Umum</h2>
            <p class="card-subtitle">Konfigurasi dasar aplikasi</p>
        </div>
        <div class="p-6 space-y-5">
            @foreach($settings->get('general', collect()) as $s)
            <div>
                <label class="form-label">{{ $s->label }}</label>
                <input type="text" name="settings[{{ $s->key }}]"
                       value="{{ old('settings.'.$s->key, $s->value) }}"
                       class="form-input">
                @if($s->description)
                <p class="text-xs text-slate-400 mt-1">{{ $s->description }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- RADIUS --}}
    <div class="card mb-6">
        <div class="card-header">
            <h2 class="card-title">RADIUS & Monitoring</h2>
            <p class="card-subtitle">Konfigurasi rekonsiliasi sesi stale</p>
        </div>
        <div class="p-6 space-y-5">
            @foreach($settings->get('radius', collect()) as $s)
            <div>
                <label class="form-label">{{ $s->label }}</label>
                <input type="number" name="settings[{{ $s->key }}]"
                       value="{{ old('settings.'.$s->key, $s->value) }}"
                       min="5" max="1440" class="form-input w-40">
                @if($s->description)
                <p class="text-xs text-slate-400 mt-1">{{ $s->description }}</p>
                @endif
            </div>
            @endforeach
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="text-amber-800 text-sm font-semibold mb-1">Rekomendasi Konfigurasi NAS</p>
                <p class="text-amber-700 text-xs">Aktifkan Interim-Update di MikroTik: <code class="bg-amber-100 px-1 rounded">RADIUS > Incoming > set interim-update=1m</code>. Threshold stale sebaiknya 2× interval Interim-Update.</p>
            </div>
        </div>
    </div>

    {{-- Billing --}}
    <div class="card mb-6">
        <div class="card-header">
            <h2 class="card-title">Billing</h2>
            <p class="card-subtitle">Konfigurasi invoice dan isolir otomatis</p>
        </div>
        <div class="p-6 space-y-5">
            @foreach($settings->get('billing', collect()) as $s)
            <div>
                @if($s->type === 'boolean')
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="settings[{{ $s->key }}]" value="0">
                    <input type="checkbox" name="settings[{{ $s->key }}]" value="1"
                           class="w-4 h-4 rounded accent-indigo-600"
                           {{ $s->value ? 'checked' : '' }}>
                    <span class="form-label mb-0">{{ $s->label }}</span>
                </label>
                @else
                <label class="form-label">{{ $s->label }}</label>
                <input type="number" name="settings[{{ $s->key }}]"
                       value="{{ old('settings.'.$s->key, $s->value) }}"
                       min="1" max="30" class="form-input w-32">
                @endif
                @if($s->description)
                <p class="text-xs text-slate-400 mt-1">{{ $s->description }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn-primary">Simpan Pengaturan</button>
    </div>
</form>
</div>
@endsection
