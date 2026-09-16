<div data-plan-form data-initial-type="{{ old('type', $plan->type ?? 'voucher') }}">
    {{-- Nama Paket --}}
    <div>
        <label class="form-label">Nama Paket <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}"
            placeholder="cth: 5Mbps Bulanan / Voucher 3 Jam"
            class="form-input @error('name') border-red-400 @enderror">
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    {{-- Tipe --}}
    <div>
        <label class="form-label">Tipe Paket <span class="text-red-500">*</span></label>
        <select name="type" id="plan-type" class="form-input @error('type') border-red-400 @enderror">
            <option value="voucher">Voucher</option>
            <option value="member">Member</option>
        </select>
        @error('type') <p class="form-error">{{ $message }}</p> @enderror
        <p id="plan-type-help" class="text-xs text-slate-400 mt-1"></p>
    </div>

    {{-- Durasi Voucher saja --}}
    <div id="voucher-duration-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4" hidden>
        <div>
            <label class="form-label">Durasi <span class="text-red-500">*</span></label>
            <input type="number" name="duration_value" min="1"
                value="{{ old('duration_value', $plan->duration_value ?? $plan->duration_days ?? '') }}"
                :disabled="type !== 'voucher'"
                placeholder="cth: 3"
                class="form-input @error('duration_value') border-red-400 @enderror">
            @error('duration_value') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label">Satuan <span class="text-red-500">*</span></label>
            <select name="duration_unit" class="form-input @error('duration_unit') border-red-400 @enderror"
                :disabled="type !== 'voucher'">
                @foreach(['minutes'=>'Menit','hours'=>'Jam','days'=>'Hari'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('duration_unit', $plan->duration_unit ?? 'days') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('duration_unit') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Nilai default internal untuk Member: 1 bulan --}}
    <input type="hidden" name="duration_value" value="1" id="member-duration-value" disabled>
    <input type="hidden" name="duration_unit" value="months" id="member-duration-unit" disabled>

    {{-- Harga --}}
    <div>
        <label class="form-label">Harga (Rp) <span class="text-red-500">*</span></label>
        <input type="number" name="price" min="0"
            value="{{ old('price', $plan->price ?? '') }}"
            placeholder="cth: 150000"
            class="form-input @error('price') border-red-400 @enderror">
        @error('price') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    {{-- QoS --}}
    <div class="border-t border-slate-200 pt-5 mt-2">
        <label class="form-label">MikroTik Rate Limit <span class="text-red-500">*</span></label>
        <input type="text" name="mikrotik_rate_limit"
            value="{{ old('mikrotik_rate_limit', ($plan->mikrotik_rate_limit ?? null) ?: ($plan->exists ?? false ? app(\App\Services\QosService::class)->rateLimit($plan) : '')) }}"
            placeholder="cth: 5M/2M"
            class="form-input font-mono @error('mikrotik_rate_limit') border-red-400 @enderror">
        @error('mikrotik_rate_limit') <p class="form-error">{{ $message }}</p> @enderror
        <p class="text-xs text-slate-400 mt-1">
            Isi langsung format RouterOS. Contoh: <code>5M/2M</code>, atau gunakan format lengkap seperti <code>10M/5M 20M/10M 5M/2M 10/10 8</code>.
        </p>
    </div>

    {{-- RADIUS Group --}}
    <div>
        <label class="form-label">Nama Group RADIUS <span class="text-red-500">*</span></label>
        <input type="text" name="radius_group_name"
            value="{{ old('radius_group_name', $plan->radius_group_name ?? '') }}"
            placeholder="cth: voucher-3jam-5mbps"
            class="form-input font-mono @error('radius_group_name') border-red-400 @enderror">
        <p class="text-xs text-slate-400 mt-1">Group FreeRADIUS. Harus unik.</p>
        @error('radius_group_name') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    {{-- Deskripsi --}}
    <div>
        <label class="form-label">Deskripsi <span class="text-slate-400 font-normal">— opsional</span></label>
        <textarea name="description" rows="2" placeholder="Catatan tambahan tentang paket ini..."
            class="form-input resize-none @error('description') border-red-400 @enderror">{{ old('description', $plan->description ?? '') }}</textarea>
        @error('description') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    {{-- Status --}}
    <div class="flex items-center gap-3">
        <input type="checkbox" id="is_active" name="is_active" value="1"
            @checked(old('is_active', $plan->is_active ?? true))
            class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
        <label for="is_active" class="text-sm text-slate-700">Paket aktif</label>
    </div>
    <script>
        (() => {
            const form = document.querySelector('[data-plan-form]');
            if (!form) return;

            const type = document.getElementById('plan-type');
            const voucherDuration = document.getElementById('voucher-duration-fields');
            const memberDurationValue = document.getElementById('member-duration-value');
            const memberDurationUnit = document.getElementById('member-duration-unit');
            const help = document.getElementById('plan-type-help');

            const sync = () => {
                const isVoucher = type.value === 'voucher';
                voucherDuration.style.display = isVoucher ? '' : 'none';
                voucherDuration.querySelectorAll('input, select').forEach((el) => {
                    el.disabled = !isVoucher;
                });
                memberDurationValue.disabled = isVoucher;
                memberDurationUnit.disabled = isVoucher;
                help.textContent = isVoucher
                    ? 'Voucher mulai dihitung saat first login.'
                    : 'Member menggunakan periode bulanan kalender. Tanggal mulai dan expired diatur pada akun member.';
            };

            type.addEventListener('change', sync);
            sync();
        })();
    </script>
</div>
