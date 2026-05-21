@if(session('success') || session('error') || session('warning') || session('info'))
<div id="flash-msg"
     class="fixed top-4 right-4 z-50 max-w-sm w-full shadow-xl rounded-2xl overflow-hidden animate-flash-in"
     role="alert" aria-live="polite">
    @if(session('success'))
    <div class="flex items-start gap-3 bg-green-50 border border-green-200 rounded-2xl px-4 py-3.5">
        <svg class="text-green-500 shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        <button onclick="document.getElementById('flash-msg').remove()" class="ml-auto text-green-400 hover:text-green-600">&#215;</button>
    </div>
    @elseif(session('error'))
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-2xl px-4 py-3.5">
        <svg class="text-red-500 shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        <button onclick="document.getElementById('flash-msg').remove()" class="ml-auto text-red-400 hover:text-red-600">&#215;</button>
    </div>
    @elseif(session('warning'))
    <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3.5">
        <svg class="text-amber-500 shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
        <p class="text-sm font-medium text-amber-800">{{ session('warning') }}</p>
        <button onclick="document.getElementById('flash-msg').remove()" class="ml-auto text-amber-400 hover:text-amber-600">&#215;</button>
    </div>
    @elseif(session('info'))
    <div class="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-2xl px-4 py-3.5">
        <svg class="text-blue-500 shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16.01"/></svg>
        <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
        <button onclick="document.getElementById('flash-msg').remove()" class="ml-auto text-blue-400 hover:text-blue-600">&#215;</button>
    </div>
    @endif
</div>
<style>
@keyframes flash-in {
    from { opacity: 0; transform: translateX(100%); }
    to   { opacity: 1; transform: translateX(0); }
}
.animate-flash-in { animation: flash-in 300ms cubic-bezier(0.34,1.56,0.64,1) forwards; }
</style>
<script>
    setTimeout(() => {
        const el = document.getElementById('flash-msg');
        if (el) {
            el.style.transition = 'opacity 400ms';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        }
    }, 4000);
</script>
@endif
