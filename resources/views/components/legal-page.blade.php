@props(['title', 'description', 'updated'])

{{-- Shared frame for the Terms and Privacy pages. --}}
<x-marketing-layout :title="$title" :description="$description">
    <article class="mx-auto max-w-3xl px-4 pt-16 pb-24 sm:px-6">
        <h1 class="font-display-tight text-5xl">{{ $title }}</h1>
        <p class="mt-3 text-sm text-ink-subtle">Last updated {{ $updated }}</p>

        <div class="mt-8 flex gap-3 rounded-lg border border-warning/30 bg-warning-soft px-4 py-3 text-sm">
            <x-ui.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-warning" />
            <p>BandarOS is a demonstration project. This page is sample text showing what a real policy would cover. It is not legal advice and is not a binding agreement.</p>
        </div>

        <div class="mt-10 leading-7 text-ink-muted [&_h2]:mt-10 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-ink [&_li]:mt-2 [&_p]:mt-4 [&_strong]:text-ink [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-6">
            {{ $slot }}
        </div>
    </article>
</x-marketing-layout>
