@extends('layouts.app')
@section('title', 'Pembayaran DOKU')

@section('content')
<div class="space-y-5">

    {{-- ALERT DOMAIN HTTPS (Hanya muncul jika diakses via IP lokal / non-HTTPS) --}}
    @if(!empty($isLocalOrInsecure))
        <div class="dashboard-alert flex-wrap gap-3 items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="dashboard-alert-icon text-amber-500" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
                <span class="text-xs text-amber-900 dark:text-amber-200">
                    <strong>Wajib HTTPS Domain:</strong> DOKU mewajibkan Webhook & SNAP Token menggunakan domain publik HTTPS (contoh: <code class="font-mono bg-amber-200/60 dark:bg-amber-900/60 px-1 py-0.5 rounded font-semibold text-amber-950 dark:text-amber-100">https://domain-anda.com</code>). Akses saat ini via <code>{{ request()->schemeAndHttpHost() }}</code>.
                </span>
            </div>
            <div class="flex items-center gap-2">
                <input type="url" id="custom-public-domain" placeholder="https://domain-anda.com"
                       class="form-input text-xs py-1 px-2.5 w-60 bg-white dark:bg-slate-900 border-amber-300 dark:border-amber-700/60" />
                <button type="button" id="apply-domain-btn" class="btn-secondary text-xs py-1 px-3 font-semibold text-amber-900 dark:text-amber-200 hover:bg-amber-100 flex-shrink-0">
                    Terapkan
                </button>
            </div>
        </div>
    @endif

    {{-- MAIN GRID 2-KOLOM MINIMALIS --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        {{-- KOLOM KIRI: KONFIGURASI & CREDENTIAL DOKU (7 COLS) --}}
        <div class="card lg:col-span-7">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Konfigurasi & Credential DOKU</h2>
                    <p class="card-subtitle">Pengaturan gateway pembayaran dan autentikasi merchant.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $setting->enabled ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $setting->enabled ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        {{ $setting->enabled ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                        {{ ucfirst($setting->environment) }}
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.doku.update') }}" class="p-5 space-y-4">
                @csrf @method('PUT')

                {{-- Status & Koneksi --}}
                <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3.5 space-y-3 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" name="enabled" value="1" class="w-4 h-4 rounded accent-indigo-600" {{ old('enabled', $setting->enabled) ? 'checked' : '' }}>
                            Aktifkan Pembayaran DOKU
                        </label>
                        <span class="text-[11px] text-slate-400">SNAP & VA Gateway</span>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-3 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                        <div>
                            <label class="form-label text-xs">Environment</label>
                            <select name="environment" class="form-input text-xs py-1.5">
                                <option value="sandbox" {{ old('environment', $setting->environment) === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                <option value="production" {{ old('environment', $setting->environment) === 'production' ? 'selected' : '' }}>Production</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs">Base URL DOKU API</label>
                            <input name="base_url" value="{{ old('base_url', $setting->base_url) }}" class="form-input text-xs py-1.5">
                        </div>
                    </div>
                </div>

                {{-- Credential --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Kredensial API</span>
                        <span class="text-[11px] text-slate-400">Disimpan terenkripsi</span>
                    </div>

                    <div>
                        <label class="form-label text-xs">Client ID</label>
                        <input name="client_id" value="{{ old('client_id', $setting->client_id) }}" placeholder="Contoh: CLIENT-..." class="form-input text-xs py-1.5">
                    </div>

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs">Secret Key</label>
                            <div class="relative">
                                <input id="input-secret-key" name="secret_key" type="password" autocomplete="new-password"
                                       placeholder="Kosongkan jika tidak diubah" class="form-input text-xs py-1.5 pr-8">
                                <button type="button" onclick="togglePasswordVisibility('input-secret-key', this)" tabindex="-1"
                                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                        title="Tampilkan / Sembunyikan">
                                    <svg class="w-3.5 h-3.5 eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg class="w-3.5 h-3.5 eye-closed hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label text-xs">API Key</label>
                            <div class="relative">
                                <input id="input-api-key" name="api_key" type="password" autocomplete="new-password"
                                       placeholder="Kosongkan jika tidak diubah" class="form-input text-xs py-1.5 pr-8">
                                <button type="button" onclick="togglePasswordVisibility('input-api-key', this)" tabindex="-1"
                                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                        title="Tampilkan / Sembunyikan">
                                    <svg class="w-3.5 h-3.5 eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg class="w-3.5 h-3.5 eye-closed hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Parameter Merchant --}}
                <div class="border-t border-slate-100 pt-3 dark:border-slate-800">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-2.5">Parameter Merchant</span>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="form-label text-xs">Merchant ID</label>
                            <input name="merchant_id" value="{{ old('merchant_id', $setting->merchant_id) }}" placeholder="Dari DOKU" class="form-input text-xs py-1.5">
                        </div>
                        <div>
                            <label class="form-label text-xs">Terminal ID</label>
                            <input name="terminal_id" value="{{ old('terminal_id', $setting->terminal_id) }}" placeholder="Opsional" class="form-input text-xs py-1.5">
                        </div>
                        <div>
                            <label class="form-label text-xs">Channel ID</label>
                            <input name="channel_id" value="{{ old('channel_id', $setting->channel_id ?: 'H2H') }}" class="form-input text-xs py-1.5">
                        </div>
                    </div>
                </div>

                {{-- Hidden operational paths --}}
                <input type="hidden" name="notification_path" value="{{ $setting->notification_path ?: '/webhooks/doku' }}">
                <input type="hidden" name="timeout" value="{{ $setting->timeout ?: 15 }}">
                <input type="hidden" name="private_key_path" value="{{ $setting->private_key_path }}">
                <input type="hidden" name="public_key_path" value="{{ $setting->public_key_path }}">

                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <button type="submit" class="btn-primary text-xs py-2 px-4">Simpan Pengaturan</button>
                        <button type="button" onclick="document.getElementById('form-test-koneksi-inline').submit();" class="btn-secondary text-xs py-2 px-3">
                            Test Koneksi DOKU
                        </button>
                    </div>
                    <span class="text-[11px] text-slate-400">Aktif seketika tanpa restart</span>
                </div>
            </form>

            <form id="form-test-koneksi-inline" method="POST" action="{{ route('settings.doku.test') }}" class="hidden">
                @csrf
            </form>
        </div>

        {{-- KOLOM KANAN: PARAMETER INTEGRASI RINGKAS (5 COLS) --}}
        <div class="card lg:col-span-5 space-y-4 p-5">
            <div class="border-b border-slate-100 pb-3 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="card-title">Parameter Integrasi</h2>
                    <p class="card-subtitle">Salin URL & Public Key ke DOKU Back Office.</p>
                </div>
                <span id="active-domain-indicator" class="text-[11px] text-indigo-600 dark:text-indigo-400 font-mono"></span>
            </div>

            {{-- SNAP Token URL --}}
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <label class="font-medium text-slate-700 dark:text-slate-200">SNAP Token URL</label>
                    <span class="text-[10px] font-mono text-slate-400">POST /payments/token</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <input type="text" id="url-snap-token" readonly
                           data-path="/payments/token"
                           value="{{ url('/payments/token') }}"
                           class="form-input text-xs py-1.5 font-mono bg-slate-50 dark:bg-slate-900 select-all" />
                    <button type="button" onclick="copyFromInput('url-snap-token', this)"
                            class="btn-secondary text-xs py-1.5 px-2.5 flex-shrink-0" title="Salin URL">
                        <span class="btn-copy-label">Salin</span>
                    </button>
                </div>
            </div>

            {{-- Notification / Webhook URL --}}
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <label class="font-medium text-slate-700 dark:text-slate-200">Notification / Webhook URL</label>
                    <span class="text-[10px] font-mono text-slate-400">POST /payments/notifications</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <input type="text" id="url-webhook" readonly
                           data-path="/payments/notifications"
                           value="{{ url('/payments/notifications') }}"
                           class="form-input text-xs py-1.5 font-mono bg-slate-50 dark:bg-slate-900 select-all" />
                    <button type="button" onclick="copyFromInput('url-webhook', this)"
                            class="btn-secondary text-xs py-1.5 px-2.5 flex-shrink-0" title="Salin URL">
                        <span class="btn-copy-label">Salin</span>
                    </button>
                </div>
            </div>

            {{-- Merchant Public Key Section --}}
            <div class="border-t border-slate-100 pt-3.5 space-y-2.5 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block">Merchant Public Key</span>
                        <span class="text-[11px] text-slate-400">Daftarkan di menu API Keys DOKU</span>
                    </div>
                    <span class="text-[11px] font-medium {{ $keyStatus['public_exists'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                        {{ $keyStatus['public_exists'] ? '● Siap Digunakan' : '● Belum Dibuat' }}
                    </span>
                </div>

                {{-- Action buttons --}}
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="copyTextToClipboard(document.getElementById('public-key-text').value, this)"
                            class="btn-primary text-xs py-1.5 px-3 inline-flex items-center gap-1.5 flex-1 justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span class="btn-copy-label">Salin Public Key</span>
                    </button>
                    @if($keyStatus['public_exists'])
                        <button type="button" onclick="downloadPublicKey()"
                                class="btn-secondary text-xs py-1.5 px-2.5" title="Download file .key">
                            ⬇ .key
                        </button>
                        <a href="{{ route('settings.doku.public-key') }}" target="_blank"
                           class="btn-secondary text-xs py-1.5 px-2.5" title="Buka tampilan raw">
                            Raw
                        </a>
                    @endif
                </div>

                {{-- Collapsible Preview (Tidak makan ruang vertikal permanen) --}}
                <details class="text-xs text-slate-500 rounded-lg border border-slate-100 p-2.5 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/30">
                    <summary class="cursor-pointer text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium select-none">
                        Lihat isi file key & fingerprint
                    </summary>
                    <div class="mt-2 space-y-2">
                        @if($keyStatus['fingerprint'])
                            <div class="text-[10px] font-mono break-all text-slate-500 bg-white p-1.5 rounded border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                                {{ $keyStatus['fingerprint'] }}
                            </div>
                        @endif
                        <textarea id="public-key-text" readonly rows="5"
                                  class="form-input text-[11px] font-mono bg-slate-900 text-slate-100 border-slate-700 select-all leading-tight p-2">{{ $publicKeyContent ?? 'Public key tidak ditemukan di server.' }}</textarea>
                    </div>
                </details>
            </div>
        </div>

    </div>

    {{-- CARD VIRTUAL ACCOUNT CHANNELS (TABEL KOMPAK ALA DASHBOARD) --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h2 class="card-title">Kanal Virtual Account</h2>
                <p class="card-subtitle">Nomor customer VA dibuat otomatis dari ID member saat order dibuat.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $channels->where('enabled', true)->count() }} aktif
                </span>
                <button type="button" onclick="document.getElementById('add-va-channel-drawer').classList.toggle('hidden')"
                        class="btn-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1.5 font-semibold text-indigo-600 dark:text-indigo-400">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Bank</span>
                </button>
            </div>
        </div>

        {{-- FORM TAMBAH BANK (DRAWER COLLAPSIBLE) --}}
        <div id="add-va-channel-drawer" class="hidden border-b border-slate-100 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-800/40">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">Tambah Kanal Bank VA Baru</span>
                <button type="button" onclick="document.getElementById('add-va-channel-drawer').classList.add('hidden')"
                        class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    ✕ Batal
                </button>
            </div>
            <form method="POST" action="{{ route('settings.doku.va-channels.store') }}" class="space-y-3">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label text-xs">Pilih Bank</label>
                        <select name="bank" id="doku-va-bank" class="form-input text-xs py-1.5" required>
                            <option value="">Pilih bank</option>
                            @foreach(config('doku.va_banks', []) as $code => $bank)
                                <option value="{{ $code }}">{{ $bank['name'] }}</option>
                            @endforeach
                            <option value="CUSTOM">Bank lain / Custom</option>
                        </select>
                    </div>
                </div>

                <div id="doku-va-custom" class="hidden grid sm:grid-cols-3 gap-2.5">
                    <div><label class="form-label text-xs">Kode</label><input name="custom_code" placeholder="BANK_X" class="form-input text-xs py-1.5"></div>
                    <div><label class="form-label text-xs">Nama Bank</label><input name="custom_name" placeholder="Nama bank" class="form-input text-xs py-1.5"></div>
                    <div><label class="form-label text-xs">Channel DOKU</label><input name="custom_channel" placeholder="Channel doku" class="form-input text-xs py-1.5"></div>
                </div>

                <div class="grid grid-cols-3 gap-2.5">
                    <div><label class="form-label text-xs">Merchant BIN</label><input name="merchant_bin" inputmode="numeric" pattern="[0-9]{1,8}" maxlength="8" placeholder="Contoh: 861880" class="form-input text-xs py-1.5 font-mono"></div>
                    <div><label class="form-label text-xs">Partner ID</label><input name="partner_service_id" inputmode="numeric" pattern="[0-9]{1,8}" maxlength="8" placeholder="Contoh: 86188" class="form-input text-xs py-1.5 font-mono"></div>
                    <div><label class="form-label text-xs">Prefix Customer</label><input name="customer_prefix" inputmode="numeric" maxlength="20" placeholder="0" class="form-input text-xs py-1.5 font-mono"></div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 text-xs pt-1">
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" name="enabled" value="1" checked class="rounded accent-indigo-600"> Aktif</label>
                        <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" name="is_default" value="1" class="rounded accent-indigo-600"> Jadikan Default</label>
                    </div>
                    <button class="btn-primary text-xs py-1.5 px-4" type="submit">Tambah Bank</button>
                </div>
            </form>
        </div>

        {{-- TABEL DAFTAR KANAL VA --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:border-slate-800 dark:bg-slate-800/20">
                        <th class="px-5 py-3">Bank</th>
                        <th class="px-5 py-3">Partner Service ID</th>
                        <th class="px-5 py-3">Merchant BIN</th>
                        <th class="px-5 py-3">Prefix Customer</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($channels as $channel)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-5 py-3.5 font-medium text-slate-800 dark:text-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="font-bold">{{ $channel->name }}</span>
                                @if($channel->is_default)
                                    <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-medium text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-300">Default</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-600 dark:text-slate-300">
                            {{ $channel->partner_service_id ?: '—' }}
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-600 dark:text-slate-300">
                            {{ $channel->merchant_bin ?: '—' }}
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-600 dark:text-slate-300">
                            {{ $channel->customer_prefix ?? '—' }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $channel->enabled ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $channel->enabled ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $channel->enabled ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <button type="button" onclick="document.getElementById('edit-row-{{ $channel->id }}').classList.toggle('hidden')"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline">
                                Edit
                            </button>
                        </td>
                    </tr>

                    {{-- INLINE EDIT ROW --}}
                    <tr id="edit-row-{{ $channel->id }}" class="hidden bg-slate-50/80 dark:bg-slate-800/40">
                        <td colspan="6" class="p-4 border-y border-slate-100 dark:border-slate-800">
                            <form method="POST" action="{{ route('settings.doku.va-channels.update', $channel) }}" class="space-y-3">
                                @csrf @method('PUT')
                                <div class="flex items-center justify-between pb-1">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100">Edit Konfigurasi {{ $channel->name }}</span>
                                    <button type="button" onclick="document.getElementById('edit-row-{{ $channel->id }}').classList.add('hidden')"
                                            class="text-xs text-slate-400 hover:text-slate-600">✕ Tutup</button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="form-label text-[11px]">Merchant BIN</label>
                                        <input name="merchant_bin" value="{{ $channel->merchant_bin }}" inputmode="numeric" pattern="[0-9]{1,8}" maxlength="8" placeholder="Contoh: 861880" class="form-input text-xs py-1 font-mono">
                                    </div>
                                    <div>
                                        <label class="form-label text-[11px]">Partner Service ID</label>
                                        <input name="partner_service_id" value="{{ $channel->partner_service_id }}" inputmode="numeric" pattern="[0-9]{1,8}" maxlength="8" placeholder="Contoh: 86188" class="form-input text-xs py-1 font-mono">
                                    </div>
                                    <div>
                                        <label class="form-label text-[11px]">Prefix Customer</label>
                                        <input name="customer_prefix" value="{{ $channel->customer_prefix }}" inputmode="numeric" maxlength="20" placeholder="0" class="form-input text-xs py-1 font-mono">
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-3 text-xs pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" name="enabled" value="1" {{ $channel->enabled ? 'checked' : '' }} class="rounded accent-indigo-600"> Aktif</label>
                                        <label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" name="is_default" value="1" {{ $channel->is_default ? 'checked' : '' }} class="rounded accent-indigo-600"> Jadikan Default</label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button class="btn-primary text-xs py-1 px-3.5" type="submit">Simpan Perubahan</button>
                                    </div>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('settings.doku.va-channels.destroy', $channel) }}" class="mt-2 text-right" onsubmit="return confirm('Hapus bank {{ addslashes($channel->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-[11px] text-red-500 hover:text-red-700 hover:underline">Hapus Bank Ini</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-slate-400">
                            Belum ada bank VA yang ditambahkan. Klik tombol <strong>Tambah Bank</strong> di atas untuk menambahkan.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// --- Domain Detection & URL Updating ---
function isLocalHostname(hostname) {
    if (!hostname) return true;
    if (hostname === 'localhost' || hostname === '127.0.0.1') return true;
    return /^\d{1,3}(\.\d{1,3}){3}$/.test(hostname);
}

function resolveActiveOrigin() {
    const isHttpsDomain = window.location.protocol === 'https:' && !isLocalHostname(window.location.hostname);
    if (isHttpsDomain) {
        return window.location.origin;
    }
    const savedDomain = localStorage.getItem('xd_doku_public_origin');
    if (savedDomain && savedDomain.trim() !== '') {
        return savedDomain.trim().replace(/\/+$/, '');
    }
    return window.location.origin;
}

function updateIntegrationUrls(origin) {
    const cleanOrigin = origin.replace(/\/+$/, '');
    const snapInput = document.getElementById('url-snap-token');
    const webhookInput = document.getElementById('url-webhook');
    const indicator = document.getElementById('active-domain-indicator');

    if (snapInput && snapInput.dataset.path) {
        snapInput.value = cleanOrigin + snapInput.dataset.path;
    }
    if (webhookInput && webhookInput.dataset.path) {
        webhookInput.value = cleanOrigin + webhookInput.dataset.path;
    }
    if (indicator) {
        indicator.textContent = cleanOrigin.replace(/^https?:\/\//, '');
    }
}

// --- Clipboard Copy Helper ---
function copyTextToClipboard(text, buttonElement) {
    if (!navigator.clipboard) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
            showCopyFeedback(buttonElement);
        } catch (err) {
            alert('Gagal menyalin. Silakan salin manual.');
        }
        document.body.removeChild(textarea);
        return;
    }

    navigator.clipboard.writeText(text).then(() => {
        showCopyFeedback(buttonElement);
    }).catch(() => {
        alert('Gagal menyalin. Silakan salin manual.');
    });
}

function showCopyFeedback(buttonElement) {
    if (!buttonElement) return;
    const label = buttonElement.querySelector('.btn-copy-label') || buttonElement;
    const originalText = label.textContent;
    label.textContent = '✔ Tersalin!';
    buttonElement.disabled = true;
    setTimeout(() => {
        label.textContent = originalText;
        buttonElement.disabled = false;
    }, 1800);
}

function copyFromInput(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;
    copyTextToClipboard(input.value, button);
}

// --- Toggle Password Visibility ---
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const eyeOpen = button.querySelector('.eye-open');
    const eyeClosed = button.querySelector('.eye-closed');

    if (input.type === 'password') {
        input.type = 'text';
        if (eyeOpen) eyeOpen.classList.add('hidden');
        if (eyeClosed) eyeClosed.classList.remove('hidden');
    } else {
        input.type = 'password';
        if (eyeOpen) eyeOpen.classList.remove('hidden');
        if (eyeClosed) eyeClosed.classList.add('hidden');
    }
}

// --- Download Public Key ---
function downloadPublicKey() {
    const text = document.getElementById('public-key-text')?.value;
    if (!text) return;
    const blob = new Blob([text], { type: 'application/x-pem-file' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'doku-public.key';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// --- Lifecycle Initialization ---
document.addEventListener('DOMContentLoaded', function () {
    // VA custom bank select toggle
    const select = document.getElementById('doku-va-bank');
    const custom = document.getElementById('doku-va-custom');
    if (select && custom) {
        const sync = () => custom.classList.toggle('hidden', select.value !== 'CUSTOM');
        select.addEventListener('change', sync);
        sync();
    }

    // Auto-detect and populate active domain
    const initialOrigin = resolveActiveOrigin();
    const domainInput = document.getElementById('custom-public-domain');
    const applyBtn = document.getElementById('apply-domain-btn');

    if (domainInput) {
        const saved = localStorage.getItem('xd_doku_public_origin');
        if (saved) {
            domainInput.value = saved;
        } else if (!isLocalHostname(window.location.hostname) && window.location.protocol === 'https:') {
            domainInput.value = window.location.origin;
        }

        const handleApply = () => {
            let val = domainInput.value.trim();
            if (val !== '') {
                if (!/^https?:\/\//i.test(val)) {
                    val = 'https://' + val;
                    domainInput.value = val;
                }
                localStorage.setItem('xd_doku_public_origin', val);
                updateIntegrationUrls(val);
            } else {
                localStorage.removeItem('xd_doku_public_origin');
                updateIntegrationUrls(window.location.origin);
            }
        };

        if (applyBtn) {
            applyBtn.addEventListener('click', handleApply);
        }
        domainInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleApply();
            }
        });
    }

    updateIntegrationUrls(initialOrigin);
});
</script>
@endsection
