{{--
  Komponen modal konfirmasi aksi berbahaya (isolir, hapus, dll).

  Usage:
    <x-confirm-modal
        id="modal-isolir"
        title="Isolir Member?"
        message="Member tidak bisa login sampai Anda aktifkan kembali."
        confirm-label="Ya, Isolir"
        confirm-class="btn-danger"
        action-url="{{ route('members.toggle-status', $member) }}"
        preference-key="confirm_isolir_{{ $member->id }}"
    />

  Trigger:
    <button data-modal-open="modal-isolir">Isolir</button>
--}}
@props([
    'id',
    'title'         => 'Konfirmasi',
    'message'       => 'Apakah Anda yakin?',
    'confirmLabel'  => 'Ya, Lanjutkan',
    'confirmClass'  => 'btn-danger',
    'actionUrl',
    'method'        => 'PATCH',
    'preferenceKey' => null,
    'infoText'      => null,
])

<div id="{{ $id }}"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     role="dialog"
     aria-modal="true"
     aria-labelledby="{{ $id }}-title"
     data-preference-key="{{ $preferenceKey }}">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
         data-modal-close="{{ $id }}"></div>

    {{-- Panel --}}
    <div class="relative z-10 w-full max-w-sm mx-4 bg-white rounded-2xl shadow-2xl p-6 space-y-4 animate-modal-in">

        {{-- Icon --}}
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto">
            <svg class="text-red-500" width="22" height="22" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12" y2="17.01"/>
            </svg>
        </div>

        <div class="text-center">
            <h3 id="{{ $id }}-title" class="text-base font-bold text-slate-800">{{ $title }}</h3>
            <p class="text-sm text-slate-500 mt-1.5">{{ $message }}</p>
        </div>

        {{-- Info tambahan (misal: notif Tahap 1) --}}
        @if($infoText)
        <div class="px-3 py-2.5 rounded-lg bg-amber-50 border border-amber-100 text-xs text-amber-700">
            {{ $infoText }}
        </div>
        @endif

        <form method="POST" action="{{ $actionUrl }}" id="{{ $id }}-form">
            @csrf
            @method($method)

            {{-- Auto-confirm preference — disimpan ke database via API --}}
            @if($preferenceKey)
            <label class="flex items-center gap-2.5 text-sm text-slate-500 select-none cursor-pointer">
                <input type="checkbox"
                       id="{{ $id }}-pref-cb"
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                       onchange="saveConfirmPref('{{ $preferenceKey }}', this.checked)">
                Jangan tanya lagi untuk aksi ini
            </label>
            @endif

            <div class="flex gap-3 pt-1">
                <button type="button"
                        data-modal-close="{{ $id }}"
                        class="btn-secondary flex-1">
                    Batal
                </button>
                <button type="submit" class="{{ $confirmClass }} flex-1">
                    {{ $confirmLabel }}
                </button>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
/**
 * Cache preferensi yang sudah diload dari server agar tidak request ulang setiap kali.
 * Diisi saat DOMContentLoaded via loadUserPrefs().
 */
window._userPrefs = window._userPrefs || {};

/**
 * Load semua preferensi user dari database (1x per halaman).
 */
async function loadUserPrefs() {
    try {
        const res = await fetch('/user/preferences', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (res.ok) {
            window._userPrefs = await res.json();
        }
    } catch (e) {
        // Gagal load pref — tidak masalah, modal akan tetap tampil
        console.warn('Could not load user preferences:', e);
    }
}

/**
 * Simpan preferensi konfirmasi ke database.
 */
async function saveConfirmPref(key, value) {
    try {
        await fetch('/user/preferences', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ key, value: value ? '1' : '0' }),
        });
        // Update local cache
        window._userPrefs[key] = value ? '1' : '0';
    } catch (e) {
        console.warn('Could not save preference:', e);
    }
}

/**
 * Buka modal. Jika user sudah set "jangan tanya lagi", langsung submit.
 */
function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;

    const prefKey = el.dataset.preferenceKey;
    if (prefKey && window._userPrefs[prefKey] === '1') {
        // Auto-confirm: langsung submit form tanpa tampilkan modal
        const form = document.getElementById(id + '-form');
        if (form) { form.submit(); return; }
    }

    el.classList.remove('hidden');
    el.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hidden');
    el.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    // Load preferensi dari server
    loadUserPrefs();

    // Open triggers
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            openModal(btn.dataset.modalOpen);
        });
    });

    // Close triggers (backdrop + tombol batal)
    document.querySelectorAll('[data-modal-close]').forEach(el => {
        el.addEventListener('click', () => closeModal(el.dataset.modalClose));
    });

    // Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[role="dialog"]:not(.hidden)').forEach(d => {
                closeModal(d.id);
            });
        }
    });
});
</script>
<style>
@keyframes modal-in {
    from { opacity: 0; transform: scale(0.92) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-modal-in {
    animation: modal-in 200ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
</style>
@endpush
@endonce
