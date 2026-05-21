@props(['route', 'icon' => 'circle'])

@php
    $isActive = request()->routeIs($route) || request()->routeIs($route . '.*');
@endphp

<a href="{{ route($route) }}"
   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
          {{ $isActive
              ? 'text-white'
              : 'text-slate-400 hover:text-slate-200 hover:bg-white/5' }}"
   @if($isActive)
   style="background: linear-gradient(135deg, rgba(99,102,241,0.25) 0%, rgba(139,92,246,0.15) 100%); box-shadow: inset 0 0 0 1px rgba(99,102,241,0.2);"
   @endif
>
    {{-- Active indicator --}}
    @if($isActive)
    <span class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-r-full bg-indigo-400" style="position:relative; left:auto; top:auto; transform:none; flex-shrink:0; width:3px; height:18px; border-radius:0 4px 4px 0; background: linear-gradient(180deg,#818cf8,#a78bfa); margin-left:-12px; margin-right:9px;"></span>
    @endif

    {{-- Icon --}}
    <svg class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ $isActive ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-300' }}"
         width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="{{ $isActive ? '2.2' : '1.8' }}" stroke-linecap="round" stroke-linejoin="round">
        @switch($icon)
            @case('home')
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            @break
            @case('ticket')
                <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2z"/>
            @break
            @case('layers')
                <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                <polyline points="2 17 12 22 22 17"/>
                <polyline points="2 12 12 17 22 12"/>
            @break
            @case('users')
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            @break
            @case('package')
                <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/>
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                <line x1="12" y1="22.08" x2="12" y2="12"/>
            @break
            @case('router')
                <rect x="2" y="9" width="20" height="6" rx="1"/>
                <path d="M12 9V3"/><path d="M8 9V5"/><path d="M16 9V5"/>
            @break
            @case('bar-chart-2')
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            @break
            @case('file-text')
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            @break
            @default
                <circle cx="12" cy="12" r="4"/>
        @endswitch
    </svg>

    {{-- Label --}}
    <span class="truncate">{{ $slot }}</span>

    {{-- Active glow dot --}}
    @if($isActive)
    <span class="ml-auto w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:#818cf8; box-shadow: 0 0 6px rgba(129,140,248,0.8);"></span>
    @endif
</a>
