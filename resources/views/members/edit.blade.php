@extends('layouts.app')
@section('title', 'Edit Member — ' . $member->username)

@section('topbar-actions')
    <a href="{{ route('members.show', $member) }}" class="btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Kembali
    </a>
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('members.update', $member) }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="card">
            <div class="card-header">Informasi Akun</div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label">Username</label>
                    <input type="text" value="{{ $member->username }}" class="form-input bg-slate-50 cursor-not-allowed" disabled>
                    <p class="text-xs text-slate-400 mt-1">Username tidak dapat diubah.</p>
                </div>
                <div>
                    <label class="form-label">Password Baru</label>
                    <div class="relative">
                        <input type="password" name="password" id="password"
                            class="form-input pr-10 @error('password') border-red-400 @enderror"
                            placeholder="Kosongkan jika tidak ingin mengubah"
                            autocomplete="new-password">
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
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" {{ $member->plan_id == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} &mdash; {{ $plan->price_label }} / {{ $plan->duration_days }} hari
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Simultaneous Use</label>
                        <input type="number" name="simultaneous_use"
                            value="{{ old('simultaneous_use', $member->simultaneous_use) }}"
                            min="1" max="10" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="active"   {{ $member->status === 'active'   ? 'selected' : '' }}>Aktif</option>
                            <option value="isolated" {{ $member->status === 'isolated' ? 'selected' : '' }}>Isolir</option>
                            <option value="expired"  {{ $member->status === 'expired'  ? 'selected' : '' }}>Expired</option>
                            <option value="inactive" {{ $member->status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label">Expired At</label>
                    <input type="datetime-local" name="expired_at"
                        value="{{ old('expired_at', $member->expired_at?->format('Y-m-d\TH:i')) }}"
                        class="form-input">
                    <p class="text-xs text-slate-400 mt-1">Ubah untuk perpanjang masa aktif secara manual.</p>
                </div>
                <div>
                    <label class="form-label">Harga (Price Snapshot)</label>
                    <input type="number" name="price_snapshot"
                        value="{{ old('price_snapshot', $member->price_snapshot) }}"
                        min="0" class="form-input">
                    <p class="text-xs text-slate-400 mt-1">Harga saat member dibuat. Dipakai di laporan.</p>
                </div>
                <div>
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" rows="2" class="form-input"
                        placeholder="Catatan opsional...">{{ old('notes', $member->notes) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
            <a href="{{ route('members.show', $member) }}" class="btn-ghost">Batal</a>
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
