@extends('layouts.app')
@section('title', 'Buat Invoice')

@section('content')
<div class="max-w-xl">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Buat Invoice untuk {{ $member->username }}</h3>
        </div>
        <div class="card-body space-y-4">
            <form method="POST" action="{{ route('billing.store') }}">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->id }}">

                <div>
                    <label class="form-label">Nominal (Rp)</label>
                    <input type="number" name="amount" value="{{ old('amount', $member->price_snapshot) }}"
                           class="form-input" required min="1">
                    @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Awal Periode Layanan</label>
                    <input type="date" name="period_start"
                           value="{{ old('period_start', $defaultPeriodStart->format('Y-m-d')) }}"
                           class="form-input" required>
                    @error('period_start') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="text-xs text-slate-400 mt-1">Member expired memulai periode dari hari ini secara default. Admin dapat mengubah tanggal ini.</p>
                </div>

                <div>
                    <label class="form-label">Akhir Periode Layanan</label>
                    <input type="date" name="period_end"
                           value="{{ old('period_end', $defaultPeriodEnd->format('Y-m-d')) }}"
                           class="form-input" required>
                    @error('period_end') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Jatuh Tempo Pembayaran</label>
                    <input type="date" name="due_date"
                           value="{{ old('due_date', now()->addDays(7)->format('Y-m-d')) }}"
                           class="form-input" required>
                    @error('due_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Catatan (opsional)</label>
                    <textarea name="notes" rows="3" class="form-input">{{ old('notes') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary">Buat Invoice</button>
                    <a href="{{ route('members.show', $member) }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
