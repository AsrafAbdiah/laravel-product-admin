<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

{{-- Apply the saved (or OS) theme before the page paints, to avoid a flash of the wrong theme. --}}
<script nonce="{{ Vite::cspNonce() }}">
    (function () {
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
