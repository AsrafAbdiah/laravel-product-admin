@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    @auth
        @php
            // label => [url, route pattern that marks the link as active]
            $navLinks = [
                'Products' => [route('products.index'), 'products.*'],
                'Categories' => [route('categories.index'), 'categories.*'],
            ];
        @endphp
        <nav class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                    <a href="{{ route('products.index') }}" class="text-lg font-semibold text-slate-900">{{ config('app.name') }}</a>
                    @foreach ($navLinks as $label => [$url, $pattern])
                        <a href="{{ $url }}"
                           class="text-sm {{ request()->routeIs($pattern) ? 'font-semibold text-indigo-600' : 'text-slate-600 hover:text-slate-900' }}"
                           @if (request()->routeIs($pattern)) aria-current="page" @endif>
                            {{ $label }}
                        </a>
                    @endforeach
                    <a href="{{ url('/api/documentation') }}" target="_blank" class="text-sm text-slate-600 hover:text-slate-900">API Docs</a>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-slate-500">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-600 hover:text-red-600">Log out</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('success'))
            <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{-- Bulk delete errors have no form field to sit under, so show them here. --}}
        @if ($errors->has('ids') || $errors->has('ids.*'))
            <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ $errors->first('ids') ?: $errors->first('ids.*') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
