<x-app-layout>
    <x-slot name="title">Daftar Voucher</x-slot>

    <div class="space-y-5">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Daftar Voucher</h1>
                <p class="text-sm text-gray-500 mt-0.5">Kelola dan monitor semua voucher.</p>
            </div>
            <a href="{{ route('vouchers.create') }}" class="btn btn-primary">
                <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                Generate Voucher
            </a>
        </div>

        {{-- Flash --}}
        @if(session('success'))
        <div class="alert alert-success">
            <x-heroicon-o-check-circle class="w-5 h-5 flex-shrink-0" />
            <span>{{ session('success') }}</span>
        </div>
        @endif

        {{-- Filter --}}
        <form method="GET" class="card">
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari username..."
                        class="form-input sm:col-span-1">

                    <select name="batch_id" class="form-select">
                        <option value="">Semua Batch</option>
                        @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                            {{ $batch->batch_code }} — {{ $batch->plan->name }} ({{ $batch->quantity }} pcs)
                        </option>
                        @endforeach
                    </select>

                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Aktif</option>
                        <option value="used"     {{ request('status') === 'used'     ? 'selected' : '' }}>Digunakan</option>
                        <option value="expired"  {{ request('status') === 'expired'  ? 'selected' : '' }}>Expired</option>
                        <option value="isolated" {{ request('status') === 'isolated' ? 'selected' : '' }}>Isolir</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary flex-1">Filter</button>
                        <a href="{{ route('vouchers.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </div>
        </form>

        {{-- Tabel --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username / Password</th>
                            <th>Batch</th>
                            <th>Paket</th>
                            <th>Harga</th>
                            <th>First Login</th>
                            <th>Expired</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                        <tr>
                            <td>
                                <span class="font-mono font-medium text-sm">{{ $voucher->username }}</span>
                                @if($voucher->is_printed)
                                <span class="ml-1 badge badge-gray text-xs">Dicetak</span>
                                @endif
                            </td>
                            <td class="text-sm text-gray-500">{{ $voucher->batch->batch_code ?? '-' }}</td>
                            <td class="text-sm">{{ $voucher->plan->name ?? '-' }}</td>
                            <td class="text-sm tabular-nums">{{ $voucher->price_label }}</td>
                            <td class="text-sm text-gray-500">
                                {{ $voucher->first_login_at ? $voucher->first_login_at->format('d M Y H:i') : '-' }}
                            </td>
                            <td class="text-sm text-gray-500">
                                {{ $voucher->expired_at ? $voucher->expired_at->format('d M Y') : '-' }}
                            </td>
                            <td>
                                <span class="badge badge-{{ $voucher->status_color }}">
                                    {{ $voucher->status_label }}
                                </span>
                            </td>
                            <td class="text-right">
                                <button class="icon-btn" title="Detail">
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-12">
                                <div class="text-gray-400">
                                    <x-heroicon-o-ticket class="w-10 h-10 mx-auto mb-2 opacity-40" />
                                    <p class="text-sm">Belum ada voucher.</p>
                                    <a href="{{ route('vouchers.create') }}" class="btn btn-primary mt-3">Generate Sekarang</a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($vouchers->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $vouchers->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
