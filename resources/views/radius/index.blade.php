@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">FreeRADIUS Management</h1>
            <p class="text-sm text-gray-500">Setup, configure, health check, dan reconciliation.</p>
        </div>
        <form method="POST" action="{{ route('freeradius.setup') }}">
            @csrf
            <button class="px-4 py-2 rounded bg-indigo-600 text-white">Setup / Re-setup</button>
        </form>
    </div>

    @if(session('success'))<div class="p-3 rounded bg-green-50 text-green-700">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="p-3 rounded bg-red-50 text-red-700">{{ session('error') }}</div>@endif

    <div class="grid md:grid-cols-2 gap-4">
        <div class="rounded border p-5 bg-white">
            <h2 class="font-semibold mb-3">Environment</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt>Installed</dt><dd>{{ $environment['installed'] ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between"><dt>Version</dt><dd>{{ $environment['version'] ?: '-' }}</dd></div>
                <div class="flex justify-between"><dt>Service</dt><dd>{{ $environment['service_active'] ? 'Active' : 'Inactive' }}</dd></div>
                <div class="flex justify-between"><dt>Config</dt><dd class="text-right">{{ $environment['config_dir'] ?: '-' }}</dd></div>
                <div class="flex justify-between"><dt>RADIUS DB</dt><dd>{{ $environment['database']['ok'] ? 'Reachable' : 'Unavailable' }}</dd></div>
                <div class="flex justify-between"><dt>Laravel privileged access</dt><dd class="text-right {{ $environment['privileged_access']['ok'] ? 'text-green-700' : 'text-red-700' }}">{{ $environment['privileged_access']['ok'] ? 'Ready' : 'Not Ready' }}</dd></div>
            </dl>
        </div>

        <div class="rounded border p-5 bg-white">
            <h2 class="font-semibold mb-3">Health</h2>
            <div class="mb-4 font-medium {{ $health['healthy'] ? 'text-green-700' : 'text-red-700' }}">
                {{ $health['healthy'] ? 'Healthy / Ready' : 'Not Healthy' }}
            </div>
            <dl class="space-y-2 text-sm">
                @foreach($health['checks'] as $name => $ok)
                    <div class="flex justify-between"><dt>{{ str_replace('_', ' ', ucfirst($name)) }}</dt><dd>{{ $ok ? 'PASS' : 'FAIL' }}</dd></div>
                @endforeach
            </dl>
        </div>
    </div>

    <div class="rounded border p-5 bg-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold">Configuration Reconciliation</h2>
                <p class="text-sm text-gray-500">Generate → validate → apply → health check.</p>
            </div>
            <form method="POST" action="{{ route('freeradius.reconcile') }}">
                @csrf
                <button class="px-4 py-2 rounded border">Run Reconciliation</button>
            </form>
        </div>
    </div>
</div>
@endsection
