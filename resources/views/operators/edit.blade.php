@extends('layouts.app')
@section('title', 'Edit Operator')

@section('content')
<div class="max-w-lg">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Edit Operator — {{ $operator->name }}</h2>
        <p class="card-subtitle">Kosongkan password jika tidak ingin mengubahnya</p>
    </div>
    <div class="p-6">
        @if($errors->any())
        <div class="flash-error mb-5">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif
        <form method="POST" action="{{ route('operators.update', $operator) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $operator->name) }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $operator->email) }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Password Baru</label>
                <input type="password" name="password" class="form-input" placeholder="Kosongkan jika tidak diubah">
            </div>
            <div>
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" class="form-input">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="{{ route('operators.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
