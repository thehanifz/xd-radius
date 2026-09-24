@extends('layouts.app')
@section('title', 'Pembayaran DOKU')

@section('content')
<div class="max-w-4xl space-y-5">
    <div class="card">
        <div class="card-header">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="card-title">Pembayaran DOKU</h2>
                    <p class="card-subtitle">Kelola koneksi dan metode pembayaran dari satu halaman.</p>
                </div>
                <div class="text-right text-xs">
                    <div class="font-medium {{ $setting->enabled ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $setting->enabled ? '● Aktif' : '● Nonaktif' }}
                    </div>
                    <div class="text-slate-400 mt-1">{{ ucfirst($setting->environment) }}</div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.doku.update') }}" class="p-6 space-y-6">
            @csrf @method('PUT')

            <section>
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="font-semibold text-slate-800">Koneksi</h3>
                        <p class="text-xs text-slate-400 mt-1">Credential rahasia tersimpan terenkripsi.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="enabled" value="1" class="w-4 h-4 rounded accent-indigo-600" {{ old('enabled', $setting->enabled) ? 'checked' : '' }}>
                        Aktifkan DOKU
                    </label>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Environment</label>
                        <select name="environment" class="form-input">
                            <option value="sandbox" {{ old('environment', $setting->environment) === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ old('environment', $setting->environment) === 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Base URL</label>
                        <input name="base_url" value="{{ old('base_url', $setting->base_url) }}" class="form-input">
                    </div>
                </div>
            </section>

            <section class="border-t pt-5">
                <h3 class="font-semibold text-slate-800">Credential</h3>
                <p class="text-xs text-slate-400 mt-1">Secret yang sudah tersimpan tidak pernah ditampilkan kembali.</p>
                <div class="grid md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="form-label">Client ID</label>
                        <input name="client_id" value="{{ old('client_id', $setting->client_id) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Secret Key</label>
                        <input name="secret_key" type="password" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">API Key</label>
                        <input name="api_key" type="password" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah" class="form-input">
                    </div>
                </div>
            </section>

            <section class="border-t pt-5">
                <h3 class="font-semibold text-slate-800">Merchant</h3>
                <div class="grid md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="form-label">Merchant ID</label>
                        <input name="merchant_id" value="{{ old('merchant_id', $setting->merchant_id) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Terminal ID</label>
                        <input name="terminal_id" value="{{ old('terminal_id', $setting->terminal_id) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Channel ID</label>
                        <input name="channel_id" value="{{ old('channel_id', $setting->channel_id ?: 'H2H') }}" class="form-input">
                    </div>
                </div>
            </section>

            {{-- Keep operational paths in the form contract, but do not expose them in normal admin workflow. --}}
            <input type="hidden" name="notification_path" value="{{ $setting->notification_path ?: '/webhooks/doku' }}">
            <input type="hidden" name="timeout" value="{{ $setting->timeout ?: 15 }}">
            <input type="hidden" name="private_key_path" value="{{ $setting->private_key_path }}">
            <input type="hidden" name="public_key_path" value="{{ $setting->public_key_path }}">

            <div class="flex flex-wrap gap-3 pt-1">
                <button type="submit" class="btn-primary">Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="card-title">Virtual Account</h2>
                    <p class="card-subtitle">Cukup pilih bank. Nomor customer VA dibuat otomatis dari ID member.</p>
                </div>
                <span class="text-xs text-slate-400">{{ $channels->where('enabled', true)->count() }} aktif</span>
            </div>
        </div>

        <div class="p-6 space-y-4">
            @forelse($channels as $channel)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-slate-800">{{ $channel->name }}</span>
                                @if($channel->is_default)
                                    <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-600">Default</span>
                                @endif
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $channel->enabled ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $channel->enabled ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-400 mt-1">
                                @if($channel->merchant_bin || $channel->partner_service_id)
                                    BIN {{ $channel->merchant_bin ?: '-' }} · Partner Service ID {{ $channel->partner_service_id ?: '-' }} · Prefix {{ $channel->customer_prefix ?? '-' }}
                                @else
                                    Konfigurasi channel belum lengkap
                                @endif
                            </div>
                        </div>
                        <details>
                            <summary class="cursor-pointer text-sm text-indigo-600 hover:text-indigo-800">Edit</summary>
                            <div class="mt-4 w-full min-w-[280px] max-w-md rounded-xl bg-slate-50 border border-slate-200 p-4">
                                <form method="POST" action="{{ route('settings.doku.va-channels.update', $channel) }}" class="space-y-3">
                                    @csrf @method('PUT')
                                    <div>
                                        <div class="grid md:grid-cols-3 gap-3">
                                            <div><label class="form-label">Merchant BIN</label><input name="merchant_bin" value="{{ $channel->merchant_bin }}" inputmode="numeric" pattern="[0-9]{1,8}" maxlength="8" placeholder="contoh 861880" class="form-input"></div>
                                            <div><label class="form-label">Partner Service ID</label><input name="partner_service_id" value="{{ $channel->partner_service_id }}" inputmode="numeric" pattern="[0-9]{1,8}" maxlength="8" placeholder="contoh 86188" class="form-input"></div>
                                            <div><label class="form-label">Prefix Customer No</label><input name="customer_prefix" value="{{ $channel->customer_prefix }}" inputmode="numeric" maxlength="20" placeholder="contoh 0" class="form-input"></div>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">Nilai ini berasal dari konfigurasi channel DOKU Anda. Disimpan terenkripsi.</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-4 text-sm">
                                        <label class="flex items-center gap-2"><input type="checkbox" name="enabled" value="1" {{ $channel->enabled ? 'checked' : '' }}> Aktif</label>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1" {{ $channel->is_default ? 'checked' : '' }}> Default</label>
                                        <button class="btn-primary ml-auto" type="submit">Simpan</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('settings.doku.va-channels.destroy', $channel) }}" class="mt-3" onsubmit="return confirm('Hapus bank {{ addslashes($channel->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">Hapus bank</button>
                                </form>
                            </div>
                        </details>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500">Belum ada bank VA. Tambahkan bank yang aktif di akun DOKU Anda.</div>
            @endforelse

            <details class="rounded-xl border border-dashed border-slate-300">
                <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-indigo-600">+ Tambah Bank</summary>
                <div class="border-t border-slate-200 p-4 bg-slate-50">
                    <form method="POST" action="{{ route('settings.doku.va-channels.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="form-label">Bank</label>
                            <select name="bank" id="doku-va-bank" class="form-input" required>
                                <option value="">Pilih bank</option>
                                @foreach(config('doku.va_banks', []) as $code => $bank)
                                    <option value="{{ $code }}">{{ $bank['name'] }}</option>
                                @endforeach
                                <option value="CUSTOM">Bank lain / Custom</option>
                            </select>
                        </div>

                        <div id="doku-va-custom" class="hidden grid md:grid-cols-2 gap-3">
                            <div><label class="form-label">Kode</label><input name="custom_code" placeholder="Contoh: BANK_X" class="form-input"></div>
                            <div><label class="form-label">Nama Bank</label><input name="custom_name" placeholder="Nama bank" class="form-input"></div>
                            <div class="md:col-span-2"><label class="form-label">Channel DOKU</label><input name="custom_channel" placeholder="Channel dari dokumentasi DOKU" class="form-input"></div>
                        </div>

                        <p class="text-xs text-slate-500">Partner Service ID, Merchant BIN, dan Prefix Customer No diisi satu kali sesuai konfigurasi channel DOKU. Setelah disimpan, pembuatan VA member berjalan otomatis.</p>

                        <div class="grid md:grid-cols-3 gap-3">
                            <div><label class="form-label">Merchant BIN</label><input name="merchant_bin" inputmode="numeric" pattern="[0-9]{1,8}" maxlength="8" placeholder="contoh 861880" class="form-input"></div>
                            <div><label class="form-label">Partner Service ID</label><input name="partner_service_id" inputmode="numeric" pattern="[0-9]{1,8}" maxlength="8" placeholder="contoh 86188" class="form-input"></div>
                            <div><label class="form-label">Prefix Customer No</label><input name="customer_prefix" inputmode="numeric" maxlength="20" placeholder="contoh 0" class="form-input"></div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <label class="flex items-center gap-2"><input type="checkbox" name="enabled" value="1" checked> Aktif</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1"> Default</label>
                            <button class="btn-primary ml-auto" type="submit">Tambah Bank</button>
                        </div>
                    </form>
                </div>
            </details>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Test Koneksi</h2>
            <p class="card-subtitle">Meminta access token DOKU tanpa menampilkan token ke UI atau log.</p>
        </div>
        <div class="p-6 flex flex-wrap items-center justify-between gap-4">
            <div class="text-sm text-slate-500">Gunakan setelah credential berubah untuk memastikan koneksi masih valid.</div>
            <form method="POST" action="{{ route('settings.doku.test') }}">
                @csrf
                <button type="submit" class="btn-secondary">Test Koneksi DOKU</button>
            </form>
        </div>
    </div>

    <details class="card">
        <summary class="cursor-pointer list-none px-6 py-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="card-title">Pengaturan Lanjutan</h2>
                    <p class="card-subtitle">Pengaturan server yang biasanya tidak perlu diubah.</p>
                </div>
                <span class="text-sm text-indigo-600">Tampilkan</span>
            </div>
        </summary>
        <div class="border-t p-6 space-y-5 text-sm">
            <div class="grid md:grid-cols-2 gap-4">
                <div><span class="text-slate-500">Webhook Path</span><code class="block mt-1">{{ $setting->notification_path ?: '/webhooks/doku' }}</code></div>
                <div><span class="text-slate-500">HTTP Timeout</span><code class="block mt-1">{{ $setting->timeout ?: 15 }} detik</code></div>
            </div>
            <div>
                <div class="font-semibold text-slate-800">RSA Key Pair</div>
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Private key</span><span class="font-medium {{ $keyStatus['private_exists'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $keyStatus['private_exists'] ? 'Installed' : 'Missing' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Public key</span><span class="font-medium {{ $keyStatus['public_exists'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $keyStatus['public_exists'] ? 'Installed' : 'Missing' }}</span></div>
                    <div><span class="text-slate-500">Fingerprint</span><code class="block mt-1 text-xs break-all">{{ $keyStatus['fingerprint'] ?? 'Belum tersedia' }}</code></div>
                </div>
                @if($keyStatus['public_exists'])
                    <a href="{{ route('settings.doku.public-key') }}" target="_blank" class="btn-secondary inline-block mt-3">Lihat Public Key</a>
                @endif
                <p class="text-xs text-slate-400 mt-3">Private key tetap sebagai file server dan tidak disimpan di database.</p>
            </div>
        </div>
    </details>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('doku-va-bank');
    const custom = document.getElementById('doku-va-custom');
    if (!select || !custom) return;
    const sync = () => custom.classList.toggle('hidden', select.value !== 'CUSTOM');
    select.addEventListener('change', sync);
    sync();
});
</script>
@endsection
