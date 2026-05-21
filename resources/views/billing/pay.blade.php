@extends('layouts.app')
@section('title', 'Catat Pembayaran')

@section('content')
<div class="max-w-xl">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Catat Pembayaran — {{ $invoice->member->username }}</h3>
        </div>
        <div class="card-body">
            {{-- Ringkasan Invoice --}}
            <div class="bg-slate-50 rounded-xl px-4 py-3.5 mb-5 space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Periode</span>
                    <span class="font-medium text-slate-700">{{ $invoice->period_start->format('d M') }} – {{ $invoice->period_end->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Nominal tagihan</span>
                    <span class="font-bold text-slate-800 tabular-nums">{{ $invoice->amount_label }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Jatuh tempo</span>
                    <span class="text-slate-700 tabular-nums">{{ $invoice->due_date->format('d M Y') }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('billing.pay', $invoice) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="form-label">Nominal Dibayar (Rp)</label>
                    <input type="number" name="amount" value="{{ old('amount', $invoice->amount) }}"
                           class="form-input" required min="1">
                    @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Metode Pembayaran</label>
                    <select name="payment_method" class="form-input">
                        <option value="cash" @selected(old('payment_method','cash')==='cash')>Tunai</option>
                        <option value="transfer" @selected(old('payment_method')==='transfer')>Transfer Bank</option>
                        <option value="qris" @selected(old('payment_method')==='qris')>QRIS</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Tanggal Bayar</label>
                    <input type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}"
                           class="form-input">
                </div>

                <div>
                    <label class="form-label">Catatan (opsional)</label>
                    <textarea name="notes" rows="2" class="form-input">{{ old('notes') }}</textarea>
                </div>

                {{-- Opsi perpanjang otomatis --}}
                <div class="flex items-start gap-3 bg-indigo-50 rounded-xl px-4 py-3.5">
                    <input type="checkbox" name="renew" value="1" id="renew" class="mt-0.5"
                           {{ old('renew') ? 'checked' : '' }}>
                    <div>
                        <label for="renew" class="text-sm font-semibold text-indigo-800 cursor-pointer">
                            Perpanjang masa aktif member
                        </label>
                        <p class="text-xs text-indigo-600 mt-0.5">
                            Expired akan dihitung dari jatuh tempo lama:
                            <strong class="tabular-nums">{{ $invoice->period_end->format('d M Y') }}</strong>
                        </p>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary">Simpan Pembayaran</button>
                    <a href="{{ route('billing.show', $invoice) }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
