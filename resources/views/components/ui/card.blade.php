@props(['padding' => true, 'title' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-surface shadow-xs']) }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3.5">
            <h2 class="font-semibold text-ink">{{ $title }}</h2>
            {{ $actions ?? '' }}
        </div>
    @endif

    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>
</section>
