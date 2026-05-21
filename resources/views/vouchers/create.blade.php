@extends('layouts.app')
@section('title', 'Generate Voucher')

@section('topbar-actions')
    <a href="{{ route('vouchers.index') }}" class="btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Kembali
    </a>
@endsection

@section('content')
<div class="max-w-5xl" x-data="voucherGenerator()" x-init="init()">

    <form method="POST" action="{{ route('vouchers.generate') }}" @submit="submitting = true">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            {{-- ===== KIRI: Form ===== --}}
            <div class="lg:col-span-3 space-y-5">

                {{-- Paket --}}
                <div class="card">
                    <div class="card-header">Paket Internet</div>
                    <div class="card-body space-y-3">
                        <div>
                            <label class="form-label">Pilih Paket <span class="text-red-500">*</span></label>
                            <select name="plan_id" x-model="planId" @change="fetchPlanInfo" class="form-input @error('plan_id') border-red-400 @enderror" required>
                                <option value="">-- Pilih Paket --</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}"
                                        data-speed="{{ $plan->download_label }} / {{ $plan->upload_label }}"
                                        data-duration="{{ $plan->duration_days }} hari"
                                        data-price="{{ $plan->price_label }}"
                                        {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div x-show="planInfo" x-cloak class="p-3 bg-blue-50 border border-blue-100 rounded-lg text-sm space-y-1">
                            <div class="flex gap-2 text-slate-600">
                                <span class="text-slate-400 w-24">Kecepatan:</span>
                                <span class="font-medium" x-text="planInfo?.speed"></span>
                            </div>
                            <div class="flex gap-2 text-slate-600">
                                <span class="text-slate-400 w-24">Durasi:</span>
                                <span class="font-medium" x-text="planInfo?.duration"></span>
                            </div>
                            <div class="flex gap-2 text-slate-600">
                                <span class="text-slate-400 w-24">Harga:</span>
                                <span class="font-medium" x-text="planInfo?.price"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Format Voucher --}}
                <div class="card">
                    <div class="card-header">Format Voucher</div>
                    <div class="card-body space-y-4">

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Prefix <span class="text-slate-400 font-normal">(opsional)</span></label>
                                <input type="text" name="prefix" x-model="prefix"
                                    @input.debounce.400ms="updatePreview"
                                    value="{{ old('prefix') }}"
                                    placeholder="Contoh: HF"
                                    maxlength="10"
                                    class="form-input @error('prefix') border-red-400 @enderror">
                                @error('prefix')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label">Panjang Total <span class="text-red-500">*</span></label>
                                <input type="number" name="length" x-model.number="length"
                                    @input.debounce.400ms="updatePreview"
                                    value="{{ old('length', 8) }}"
                                    min="4" max="20"
                                    class="form-input @error('length') border-red-400 @enderror" required>
                                @error('length')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                <p class="text-xs text-slate-400 mt-1">Termasuk prefix. Min 4, max 20.</p>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Jenis Karakter <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-1">
                                @foreach([
                                    'numeric'      => 'Angka (0-9)',
                                    'alpha_upper'  => 'Huruf Besar',
                                    'alpha_lower'  => 'Huruf Kecil',
                                    'alpha'        => 'Huruf Campuran',
                                    'alphanumeric' => 'Huruf + Angka',
                                ] as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="charset_mode" value="{{ $value }}"
                                        x-model="charsetMode"
                                        @change="updatePreview"
                                        {{ old('charset_mode', 'alphanumeric') === $value ? 'checked' : '' }}
                                        class="sr-only peer">
                                    <div class="px-3 py-2 text-sm border rounded-lg text-center cursor-pointer
                                        peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700
                                        border-slate-200 text-slate-600 hover:border-blue-300 transition-colors">
                                        {{ $label }}
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('charset_mode')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>

                {{-- Jumlah & Catatan --}}
                <div class="card">
                    <div class="card-header">Jumlah & Catatan</div>
                    <div class="card-body space-y-4">
                        <div>
                            <label class="form-label">Jumlah Voucher <span class="text-red-500">*</span></label>
                            <input type="number" name="quantity" x-model.number="quantity"
                                value="{{ old('quantity', 10) }}"
                                min="1" max="500"
                                class="form-input @error('quantity') border-red-400 @enderror" required>
                            @error('quantity')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            <p class="text-xs text-slate-400 mt-1">Maksimal 500 voucher per generate.</p>
                        </div>
                        <div>
                            <label class="form-label">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input type="text" name="notes" value="{{ old('notes') }}"
                                placeholder="Contoh: Voucher Hari Raya 2026"
                                class="form-input @error('notes') border-red-400 @enderror">
                            @error('notes')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- ===== KANAN: Preview ===== --}}
            <div class="lg:col-span-2">
                <div class="card sticky top-6">
                    <div class="card-header">Preview Format</div>
                    <div class="card-body">

                        <p class="text-xs text-slate-500 mb-3">Contoh username yang akan digenerate:</p>

                        <div class="space-y-2" x-show="!loadingPreview">
                            <template x-for="ex in examples" :key="ex">
                                <div class="font-mono text-sm px-3 py-2 bg-slate-50 rounded border border-slate-200" x-text="ex"></div>
                            </template>
                            <div x-show="examples.length === 0" class="text-sm text-slate-400 italic">Isi form untuk melihat preview.</div>
                        </div>

                        <div x-show="loadingPreview" class="space-y-2">
                            <div class="h-9 bg-slate-100 rounded animate-pulse"></div>
                            <div class="h-9 bg-slate-100 rounded animate-pulse"></div>
                            <div class="h-9 bg-slate-100 rounded animate-pulse"></div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-400 space-y-1">
                            <div>Panjang: <span class="font-medium text-slate-600" x-text="length + ' karakter'"></span></div>
                            <div>Suffix: <span class="font-medium text-slate-600" x-text="Math.max(0, length - prefix.length) + ' karakter'"></span></div>
                            <div x-show="length - prefix.length < 2" class="text-amber-500">
                                &#9888; Suffix terlalu pendek, tingkatkan panjang.
                            </div>
                        </div>

                        <div class="mt-6 space-y-2">
                            <button type="submit"
                                :disabled="submitting || !planId"
                                class="btn-primary w-full justify-center"
                                :class="{'opacity-50 cursor-not-allowed': submitting || !planId}">
                                <span x-show="!submitting">
                                    <svg class="inline w-4 h-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                    Generate <span x-text="quantity"></span> Voucher
                                </span>
                                <span x-show="submitting">Memproses...</span>
                            </button>
                            <p class="text-xs text-center text-slate-400">Username = Password untuk semua voucher.</p>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
function voucherGenerator() {
    return {
        planId: '{{ old('plan_id', '') }}',
        prefix: '{{ old('prefix', '') }}',
        length: {{ old('length', 8) }},
        charsetMode: '{{ old('charset_mode', 'alphanumeric') }}',
        quantity: {{ old('quantity', 10) }},
        planInfo: null,
        examples: [],
        loadingPreview: false,
        submitting: false,

        init() {
            this.$nextTick(() => {
                this.updatePreview();
                if (this.planId) this.fetchPlanInfo();
            });
        },

        fetchPlanInfo() {
            const select = this.$el.querySelector('select[name=plan_id]');
            const option = select?.options[select.selectedIndex];
            if (!option || !option.value) { this.planInfo = null; return; }
            this.planInfo = {
                speed:    option.dataset.speed,
                duration: option.dataset.duration,
                price:    option.dataset.price,
            };
        },

        async updatePreview() {
            if (this.length < 4 || this.length <= this.prefix.length) {
                this.examples = [];
                return;
            }
            this.loadingPreview = true;
            try {
                const res = await fetch('{{ route('vouchers.preview') }}?' + new URLSearchParams({
                    prefix: this.prefix,
                    length: this.length,
                    charset_mode: this.charsetMode,
                }));
                const data = await res.json();
                this.examples = data.examples ?? [];
            } catch (e) {
                this.examples = [];
            } finally {
                this.loadingPreview = false;
            }
        },
    }
}
</script>
@endpush
@endsection
