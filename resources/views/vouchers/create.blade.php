<x-app-layout>
    <x-slot name="title">Generate Voucher</x-slot>

    <div class="max-w-5xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Generate Voucher</h1>
                <p class="text-sm text-gray-500 mt-0.5">Buat voucher satuan atau bulk dengan konfigurasi format.</p>
            </div>
            <a href="{{ route('vouchers.index') }}" class="btn btn-secondary">
                <x-heroicon-o-arrow-left class="w-4 h-4 mr-1" />
                Kembali
            </a>
        </div>

        <form method="POST" action="{{ route('vouchers.generate') }}" x-data="voucherGenerator()" @submit="submitting = true">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                {{-- ===== KIRI: Form ===== --}}
                <div class="lg:col-span-3 space-y-5">

                    {{-- Paket --}}
                    <div class="card">
                        <div class="card-header">Paket Internet</div>
                        <div class="card-body">
                            <label class="form-label required">Pilih Paket</label>
                            <select name="plan_id" x-model="planId" @change="fetchPlanInfo" class="form-select @error('plan_id') is-invalid @enderror" required>
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
                            @error('plan_id')<p class="form-error">{{ $message }}</p>@enderror

                            <div x-show="planInfo" x-cloak class="mt-3 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg text-sm space-y-1">
                                <div class="flex gap-4">
                                    <span class="text-gray-500">Kecepatan:</span>
                                    <span class="font-medium" x-text="planInfo?.speed"></span>
                                </div>
                                <div class="flex gap-4">
                                    <span class="text-gray-500">Durasi:</span>
                                    <span class="font-medium" x-text="planInfo?.duration"></span>
                                </div>
                                <div class="flex gap-4">
                                    <span class="text-gray-500">Harga:</span>
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
                                {{-- Prefix --}}
                                <div>
                                    <label class="form-label">Prefix <span class="text-gray-400">(opsional)</span></label>
                                    <input type="text" name="prefix" x-model="prefix"
                                        @input.debounce.300ms="updatePreview"
                                        value="{{ old('prefix') }}"
                                        placeholder="Contoh: HF"
                                        maxlength="10"
                                        class="form-input @error('prefix') is-invalid @enderror">
                                    @error('prefix')<p class="form-error">{{ $message }}</p>@enderror
                                </div>

                                {{-- Panjang --}}
                                <div>
                                    <label class="form-label required">Panjang Total</label>
                                    <input type="number" name="length" x-model.number="length"
                                        @input.debounce.300ms="updatePreview"
                                        value="{{ old('length', 8) }}"
                                        min="4" max="20"
                                        class="form-input @error('length') is-invalid @enderror" required>
                                    @error('length')<p class="form-error">{{ $message }}</p>@enderror
                                    <p class="text-xs text-gray-400 mt-1">Termasuk prefix. Min 4, max 20.</p>
                                </div>
                            </div>

                            {{-- Jenis Karakter --}}
                            <div>
                                <label class="form-label required">Jenis Karakter</label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
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
                                        <div class="px-3 py-2 text-sm border rounded-lg text-center
                                            peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700
                                            dark:peer-checked:bg-primary-900/30 dark:peer-checked:text-primary-400
                                            border-gray-200 dark:border-gray-700 hover:border-primary-300 transition-colors">
                                            {{ $label }}
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                                @error('charset_mode')<p class="form-error">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>

                    {{-- Jumlah & Catatan --}}
                    <div class="card">
                        <div class="card-header">Jumlah & Catatan</div>
                        <div class="card-body space-y-4">
                            <div>
                                <label class="form-label required">Jumlah Voucher</label>
                                <input type="number" name="quantity" x-model.number="quantity"
                                    value="{{ old('quantity', 10) }}"
                                    min="1" max="500"
                                    class="form-input @error('quantity') is-invalid @enderror" required>
                                @error('quantity')<p class="form-error">{{ $message }}</p>@enderror
                                <p class="text-xs text-gray-400 mt-1">Maksimal 500 voucher per generate.</p>
                            </div>
                            <div>
                                <label class="form-label">Catatan <span class="text-gray-400">(opsional)</span></label>
                                <input type="text" name="notes" value="{{ old('notes') }}"
                                    placeholder="Contoh: Voucher Hari Raya 2026"
                                    class="form-input @error('notes') is-invalid @enderror">
                                @error('notes')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ===== KANAN: Preview ===== --}}
                <div class="lg:col-span-2">
                    <div class="card sticky top-6">
                        <div class="card-header">Preview Format</div>
                        <div class="card-body">

                            <p class="text-xs text-gray-500 mb-3">Contoh username yang akan digenerate:</p>

                            <div class="space-y-2" x-show="!loadingPreview">
                                <template x-for="ex in examples" :key="ex">
                                    <div class="font-mono text-sm px-3 py-2 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700" x-text="ex"></div>
                                </template>
                                <div x-show="examples.length === 0" class="text-sm text-gray-400 italic">Isi form untuk melihat preview.</div>
                            </div>

                            <div x-show="loadingPreview" class="space-y-2">
                                <div class="h-8 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                                <div class="h-8 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                                <div class="h-8 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400 space-y-1">
                                <div>Panjang: <span class="font-medium text-gray-600 dark:text-gray-300" x-text="length + ' karakter'"></span></div>
                                <div>Suffix: <span class="font-medium text-gray-600 dark:text-gray-300" x-text="Math.max(0, length - prefix.length) + ' karakter'"></span></div>
                                <div class="text-yellow-600 dark:text-yellow-400" x-show="length - prefix.length < 4">⚠ Suffix terlalu pendek, tingkatkan panjang atau persingkat prefix.</div>
                            </div>

                            <div class="mt-6">
                                <button type="submit" :disabled="submitting || !planId"
                                    class="btn btn-primary w-full" :class="{'opacity-50 cursor-not-allowed': submitting || !planId}">
                                    <span x-show="!submitting">
                                        <x-heroicon-o-bolt class="w-4 h-4 mr-1 inline" />
                                        Generate <span x-text="quantity"></span> Voucher
                                    </span>
                                    <span x-show="submitting">Memproses...</span>
                                </button>
                                <p class="text-xs text-center text-gray-400 mt-2">Username = Password untuk semua voucher.</p>
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
                if (this.length < 4 || this.length < this.prefix.length + 1) {
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
</x-app-layout>
