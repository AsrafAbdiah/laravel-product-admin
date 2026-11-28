<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">

{{--
    Before the page paints: mark that JavaScript runs (so animated elements can
    start hidden, see the hero styles) and apply the saved (or OS) theme, to
    avoid a flash of the wrong theme.
--}}
<script nonce="{{ Vite::cspNonce() }}">
    (function () {
        document.documentElement.classList.add('js');
        try {
            var saved = localStorage.getItem('theme');
            if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (error) {}
    })();
</script>

@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
