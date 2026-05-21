@extends('layouts.app')
@section('title', 'Edit Paket')

@section('content')
<div class="max-w-2xl">
    <div class="mb-5">
        <a href="{{ route('plans.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali ke Daftar Paket
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-slate-800">Edit Paket: {{ $plan->name }}</h2>
            <p class="text-sm text-slate-500 mt-0.5">Perubahan tidak mempengaruhi voucher/member yang sudah aktif.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('plans.update', $plan) }}" class="space-y-5">
                @csrf @method('PUT')
                @include('plans._form')
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    <a href="{{ route('plans.index') }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
