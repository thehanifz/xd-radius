@extends('layouts.app')
@section('title', 'Tambah Operator')

@section('content')
<div class="max-w-lg">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Tambah Operator Baru</h2>
        <p class="card-subtitle">Operator dapat mengelola voucher, member, dan monitoring</p>
    </div>
    <div class="p-6">
        @if($errors->any())
        <div class="flash-error mb-5">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif
        <form method="POST" action="{{ route('operators.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" class="form-input" placeholder="Minimal 8 karakter" required>
            </div>
            <div>
                <label class="form-label">Konfirmasi Password <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" class="form-input" required>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Simpan Operator</button>
                <a href="{{ route('operators.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
