@extends('layouts.app')
@section('title', 'Tambah Paket')

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
            <h2 class="font-semibold text-slate-800">Tambah Paket Internet</h2>
            <p class="text-sm text-slate-500 mt-0.5">Isi detail paket untuk voucher atau member berlangganan.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('plans.store') }}" class="space-y-5">
                @csrf
                @include('plans._form')
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary">Simpan Paket</button>
                    <a href="{{ route('plans.index') }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
