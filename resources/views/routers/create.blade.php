@extends('layouts.app')
@section('title', 'Tambah Router')

@section('content')
<div class="max-w-xl">
    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-slate-800">Tambah Router / NAS</h2>
            <p class="text-sm text-slate-500 mt-0.5">Data router MikroTik atau NAS yang terhubung ke FreeRADIUS.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('routers.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="form-label">Nama Router <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="form-input @error('name') border-red-400 @enderror"
                        placeholder="cth: Router Utama, Tower A">
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">IP Address <span class="text-red-500">*</span></label>
                        <input type="text" name="ip_address" value="{{ old('ip_address') }}"
                            class="form-input font-mono @error('ip_address') border-red-400 @enderror"
                            placeholder="192.168.1.1">
                        @error('ip_address')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">API Port <span class="text-red-500">*</span></label>
                        <input type="number" name="api_port" value="{{ old('api_port', 8728) }}"
                            class="form-input font-mono @error('api_port') border-red-400 @enderror"
                            min="1" max="65535">
                        @error('api_port')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Username API <span class="text-red-500">*</span></label>
                        <input type="text" name="api_username" value="{{ old('api_username') }}"
                            class="form-input font-mono @error('api_username') border-red-400 @enderror"
                            placeholder="admin">
                        @error('api_username')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Password API <span class="text-red-500">*</span></label>
                        <input type="password" name="api_secret"
                            class="form-input @error('api_secret') border-red-400 @enderror"
                            placeholder="••••••••">
                        @error('api_secret')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="location" value="{{ old('location') }}"
                        class="form-input @error('location') border-red-400 @enderror"
                        placeholder="cth: Gedung A Lt.3, Tower B">
                    @error('location')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-2.5">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        {{ old('is_active', true) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm text-slate-700">Router aktif</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('routers.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary">Simpan Router</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
