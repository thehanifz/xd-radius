@extends('layouts.auth')

@section('title', 'Setup Awal — RadiusManager')

@section('content')
<div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4"
         style="background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 8px 24px rgba(99,102,241,0.35);">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round">
            <circle cx="12" cy="12" r="2"/>
            <path d="M12 2a10 10 0 0 1 10 10"/><path d="M12 2a10 10 0 0 0-10 10"/>
            <path d="M12 6a6 6 0 0 1 6 6"/><path d="M12 6a6 6 0 0 0-6 6"/>
        </svg>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Setup RadiusManager</h1>
    <p class="text-slate-500 text-sm mt-1">Buat akun Super User pertama untuk memulai</p>
</div>

<div class="bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3 mb-6 flex gap-3">
    <svg class="flex-shrink-0 w-4 h-4 text-indigo-500 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <p class="text-indigo-700 text-sm">Halaman ini hanya dapat diakses satu kali. Setelah Super User dibuat, halaman ini akan tertutup otomatis.</p>
</div>

@if($errors->any())
<div class="flash-error mb-5">
    <ul class="space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('onboarding.store') }}" class="space-y-5">
    @csrf
    <div>
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="Nama administrator" required>
    </div>
    <div>
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="admin@domain.com" required>
    </div>
    <div>
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" placeholder="Minimal 8 karakter" required>
    </div>
    <div>
        <label class="form-label">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="form-input" placeholder="Ulangi password" required>
    </div>
    <button type="submit" class="btn-primary w-full">
        Buat Super User & Mulai
    </button>
</form>
@endsection
