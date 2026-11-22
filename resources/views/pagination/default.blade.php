@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-ink-muted">
            Showing <span class="font-medium text-ink tabular-nums">{{ $paginator->firstItem() }}</span>
            to <span class="font-medium text-ink tabular-nums">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-ink tabular-nums">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="grid h-9 w-9 place-items-center rounded-lg text-ink-subtle opacity-50" aria-disabled="true">
                    <x-ui.icon name="chevron-left" class="h-4 w-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"
                   class="grid h-9 w-9 place-items-center rounded-lg text-ink-muted hover:bg-surface-muted hover:text-ink">
                    <x-ui.icon name="chevron-left" class="h-4 w-4" />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-ink-subtle">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="grid h-9 min-w-9 place-items-center rounded-lg bg-brand px-2 font-medium text-on-brand tabular-nums">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Page {{ $page }}"
                               class="hidden h-9 min-w-9 place-items-center rounded-lg px-2 text-ink-muted tabular-nums hover:bg-surface-muted hover:text-ink sm:grid">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"
                   class="grid h-9 w-9 place-items-center rounded-lg text-ink-muted hover:bg-surface-muted hover:text-ink">
                    <x-ui.icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <span class="grid h-9 w-9 place-items-center rounded-lg text-ink-subtle opacity-50" aria-disabled="true">
                    <x-ui.icon name="chevron-right" class="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
