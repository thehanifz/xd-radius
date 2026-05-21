@props(['route', 'icon' => 'circle'])

@php
    $isActive = request()->routeIs($route);
@endphp

<a
    href="{{ route($route) }}"
    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-150
           {{ $isActive
                ? 'bg-blue-700/30 text-white font-medium'
                : 'text-slate-400 hover:bg-sidebar-hover hover:text-slate-200' }}"
>
    {{-- Icon --}}
    <svg class="flex-shrink-0 {{ $isActive ? 'text-blue-400' : '' }}" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        @switch($icon)
            @case('home')      <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/> @break
            @case('ticket')    <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2z"/> @break
            @case('layers')    <polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/> @break
            @case('users')     <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/> @break
            @case('file-text') <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/> @break
            @case('package')   <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/> @break
            @case('router')    <rect x="2" y="9" width="20" height="6" rx="1"/><path d="M12 9V3"/><path d="M8 9V5"/><path d="M16 9V5"/> @break
            @case('user-cog')  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/> @break
            @case('bar-chart-2') <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/> @break
            @default           <circle cx="12" cy="12" r="4"/>
        @endswitch
    </svg>
    {{ $slot }}
</a>
