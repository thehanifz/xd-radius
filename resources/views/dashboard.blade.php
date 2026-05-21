@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Welcome banner --}}
    <div class="card">
        <div class="card-body">
            <p class="text-sm text-slate-500">Selamat datang kembali,</p>
            <h2 class="text-xl font-semibold text-slate-800">{{ auth('app')->user()->name }} 👋</h2>
        </div>
    </div>

    {{-- KPI placeholder --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Total Voucher', 'value' => '—', 'color' => 'blue'],
            ['label' => 'Member Aktif',  'value' => '—', 'color' => 'green'],
            ['label' => 'Sesi Online',   'value' => '—', 'color' => 'indigo'],
            ['label' => 'Tagihan Pending','value' => '—', 'color' => 'yellow'],
        ] as $kpi)
        <div class="card">
            <div class="card-body">
                <p class="text-xs text-slate-500 mb-1">{{ $kpi['label'] }}</p>
                <p class="text-2xl font-bold text-slate-800">{{ $kpi['value'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body text-center py-12">
            <p class="text-slate-400 text-sm">Dashboard sedang dalam pengembangan — fitur akan tersedia di tahap berikutnya.</p>
        </div>
    </div>

</div>
@endsection
