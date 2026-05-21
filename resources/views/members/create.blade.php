@extends('layouts.app')
@section('title', 'Tambah Member')

@section('topbar-actions')
    <a href="{{ route('members.index') }}" class="btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Kembali
    </a>
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('members.store') }}" class="space-y-5">
        @csrf

        <div class="card">
            <div class="card-header">Informasi Akun</div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" value="{{ old('username') }}"
                        class="form-input @error('username') border-red-400 @enderror"
                        placeholder="contoh: budi.santoso"
                        autocomplete="off" required>
                    @error('username')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-slate-400 mt-1">Huruf, angka, titik, strip, underscore. Min 3 karakter.</p>
                </div>
                <div>
                    <label class="form-label">Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="password" id="password"
                            class="form-input pr-10 @error('password') border-red-400 @enderror"
                            placeholder="Min 6 karakter, berbeda dari username"
                            autocomplete="new-password" required>
                        <button type="button" onclick="togglePass()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Paket & Konfigurasi</div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Paket <span class="text-red-500">*</span></label>
                    <select name="plan_id" class="form-input @error('plan_id') border-red-400 @enderror" required>
                        <option value="">-- Pilih Paket --</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} &mdash; {{ $plan->price_label }} / {{ $plan->duration_days }} hari
                            </option>
                        @endforeach
                    </select>
                    @error('plan_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Simultaneous Use <span class="text-red-500">*</span></label>
                    <input type="number" name="simultaneous_use" value="{{ old('simultaneous_use', 1) }}"
                        min="1" max="10"
                        class="form-input w-32 @error('simultaneous_use') border-red-400 @enderror">
                    @error('simultaneous_use')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-slate-400 mt-1">Jumlah sesi login bersamaan yang diizinkan.</p>
                </div>
                <div>
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" rows="2"
                        class="form-input @error('notes') border-red-400 @enderror"
                        placeholder="Catatan opsional...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Simpan Member</button>
            <a href="{{ route('members.index') }}" class="btn-ghost">Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function togglePass() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
@endpush
@endsection
