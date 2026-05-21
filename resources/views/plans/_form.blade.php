{{-- Nama Paket --}}
<div>
    <label class="form-label">Nama Paket <span class="text-red-500">*</span></label>
    <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}"
        placeholder="cth: Paket 1 Hari 1 Mbps"
        class="form-input @error('name') border-red-400 @enderror">
    @error('name') <p class="form-error">{{ $message }}</p> @enderror
</div>

{{-- Tipe --}}
<div>
    <label class="form-label">Tipe Paket <span class="text-red-500">*</span></label>
    <select name="type" class="form-input @error('type') border-red-400 @enderror">
        <option value="voucher" @selected(old('type', $plan->type ?? 'voucher') === 'voucher')>Voucher (prepaid, kode)</option>
        <option value="member"  @selected(old('type', $plan->type ?? '') === 'member')>Member (berlangganan bulanan)</option>
    </select>
    @error('type') <p class="form-error">{{ $message }}</p> @enderror
</div>

{{-- Kecepatan --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="form-label">Download (Kbps) <span class="text-red-500">*</span></label>
        <input type="number" name="download_speed_kbps" min="1"
            value="{{ old('download_speed_kbps', $plan->download_speed_kbps ?? '') }}"
            placeholder="cth: 1024 = 1 Mbps"
            class="form-input @error('download_speed_kbps') border-red-400 @enderror">
        <p class="text-xs text-slate-400 mt-1">1 Mbps = 1024 Kbps</p>
        @error('download_speed_kbps') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="form-label">Upload (Kbps) <span class="text-red-500">*</span></label>
        <input type="number" name="upload_speed_kbps" min="1"
            value="{{ old('upload_speed_kbps', $plan->upload_speed_kbps ?? '') }}"
            placeholder="cth: 512"
            class="form-input @error('upload_speed_kbps') border-red-400 @enderror">
        @error('upload_speed_kbps') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Durasi & Harga --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="form-label">Durasi (hari) <span class="text-red-500">*</span></label>
        <input type="number" name="duration_days" min="1"
            value="{{ old('duration_days', $plan->duration_days ?? '') }}"
            placeholder="cth: 30"
            class="form-input @error('duration_days') border-red-400 @enderror">
        @error('duration_days') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="form-label">Harga (Rp) <span class="text-red-500">*</span></label>
        <input type="number" name="price" min="0"
            value="{{ old('price', $plan->price ?? '') }}"
            placeholder="cth: 15000"
            class="form-input @error('price') border-red-400 @enderror">
        @error('price') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Quota --}}
<div>
    <label class="form-label">Kuota Data (MB) <span class="text-slate-400 font-normal">— opsional, kosongkan = unlimited</span></label>
    <input type="number" name="data_quota_mb" min="1"
        value="{{ old('data_quota_mb', $plan->data_quota_mb ?? '') }}"
        placeholder="cth: 10240 = 10 GB, kosongkan jika unlimited"
        class="form-input @error('data_quota_mb') border-red-400 @enderror">
    @error('data_quota_mb') <p class="form-error">{{ $message }}</p> @enderror
</div>

{{-- RADIUS Group --}}
<div>
    <label class="form-label">Nama Group RADIUS <span class="text-red-500">*</span></label>
    <input type="text" name="radius_group_name"
        value="{{ old('radius_group_name', $plan->radius_group_name ?? '') }}"
        placeholder="cth: voucher-1hari-1mbps (hanya huruf, angka, strip)"
        class="form-input font-mono @error('radius_group_name') border-red-400 @enderror">
    <p class="text-xs text-slate-400 mt-1">Nama ini digunakan sebagai group di FreeRADIUS. Harus unik dan tidak boleh diubah setelah dipakai.</p>
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
    <label for="is_active" class="text-sm text-slate-700">Paket aktif (bisa dipilih saat generate voucher / daftar member)</label>
</div>
