@props(['title' => null])

{{-- Centred layout for pages shown to logged-out visitors (login, and later sign-up). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="min-h-full bg-canvas font-sans text-ink antialiased">
    <main class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <a href="{{ url('/') }}" class="mb-8 flex items-center gap-2.5">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand text-lg font-bold text-on-brand">B</span>
            <span class="text-2xl font-semibold tracking-tight">Bandar<span class="text-brand">OS</span></span>
        </a>

        <div class="w-full max-w-sm">
            {{ $slot }}
        </div>

        <p class="mt-8 text-xs text-ink-subtle">Inventory for small shops</p>
    </main>

    @include('partials.overlays')
</body>
</html>
