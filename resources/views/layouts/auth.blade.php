<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'RadiusManager') }} — @yield('title', 'Setup Awal')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-900">
    <div class="min-h-screen flex items-center justify-center p-6 bg-slate-900">
        <div class="w-full max-w-md">

            <div class="bg-white rounded-2xl shadow-2xl p-8 sm:p-10">
                @yield('content')
            </div>

            <p class="text-center text-xs text-slate-500 mt-5">
                RadiusManager &mdash; ISP & Hotspot Management
            </p>

        </div>
    </div>
</body>
</html>
