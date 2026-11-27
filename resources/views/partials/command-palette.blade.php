{{--
    Ctrl+K / Cmd+K command palette (Alpine component "commandPalette" in resources/js/app.js).
    Open it from anywhere with the shortcut, or dispatch "open-command-palette".
--}}
<div x-data="commandPalette(@js($commands), @js(route('search')))"
     @keydown.ctrl.k.window.prevent="toggle()" @keydown.meta.k.window.prevent="toggle()"
     @open-command-palette.window="show()">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center px-4 pt-[12vh]"
         role="dialog" aria-modal="true" aria-label="Search and jump to">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/40" @click="close()"></div>

        <div x-show="open" x-transition.scale.95.origin.top
             class="relative w-full max-w-xl overflow-hidden rounded-xl border border-line bg-surface shadow-2xl"
             @keydown.escape.prevent.stop="close()"
             @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
             @keydown.enter.prevent="choose()" @keydown.tab.prevent>
            <div class="flex items-center gap-3 border-b border-line px-4">
                <x-ui.icon name="search" class="h-5 w-5 shrink-0 text-ink-subtle" />
                <input x-ref="input" x-model="query" type="text" autocomplete="off" spellcheck="false"
                       placeholder="Search products, categories and pages…"
                       class="h-14 w-full bg-transparent text-sm text-ink outline-none placeholder:text-ink-subtle"
                       role="combobox" aria-expanded="true" aria-controls="palette-results" aria-autocomplete="list"
                       aria-label="Search products, categories and pages"
                       :aria-activedescendant="results.length ? 'palette-item-' + active : null">
                <span x-show="loading" x-cloak class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-line-strong border-t-brand" aria-hidden="true"></span>
                <kbd class="hidden rounded border border-line px-1.5 py-0.5 text-[11px] text-ink-subtle sm:block">Esc</kbd>
            </div>

            <ul id="palette-results" role="listbox" aria-label="Results" class="max-h-[min(24rem,60vh)] overflow-y-auto p-2">
                <template x-for="(item, index) in results" :key="item.group + item.url + item.label">
                    <li role="presentation">
                        <p x-show="index === 0 || results[index - 1].group !== item.group" x-text="item.group"
                           class="px-3 pt-2 pb-1 text-[11px] font-medium tracking-wide text-ink-subtle uppercase" aria-hidden="true"></p>
                        <a :href="item.url" :id="'palette-item-' + index" role="option" :aria-selected="active === index" tabindex="-1"
                           @click.prevent="choose(item)" @mousemove="active = index"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm"
                           :class="active === index ? 'bg-brand-soft text-brand-ink' : 'text-ink'">
                            <span class="shrink-0 text-ink-subtle" :class="active === index && 'text-brand'">
                                <span x-show="item.group === 'Pages' && item.action !== 'theme'"><x-ui.icon name="arrow-right" class="h-4 w-4" /></span>
                                <span x-show="item.action === 'theme'"><x-ui.icon name="moon" class="h-4 w-4" /></span>
                                <span x-show="item.group === 'Products'"><x-ui.icon name="box" class="h-4 w-4" /></span>
                                <span x-show="item.group === 'Categories'"><x-ui.icon name="tag" class="h-4 w-4" /></span>
                            </span>
                            <span class="min-w-0 flex-1 truncate" x-text="item.label"></span>
                            <span x-show="item.hint" x-text="item.hint" class="shrink-0 text-xs text-ink-subtle"></span>
                        </a>
                    </li>
                </template>

                <li x-show="results.length === 0 && !loading" class="px-3 py-8 text-center text-sm text-ink-muted" role="presentation">
                    No matches for “<span x-text="query"></span>”.
                </li>
            </ul>

            <div class="hidden items-center gap-4 border-t border-line px-4 py-2.5 text-xs text-ink-subtle sm:flex" aria-hidden="true">
                <span><kbd class="font-sans">↑</kbd> <kbd class="font-sans">↓</kbd> to move</span>
                <span><kbd class="font-sans">Enter</kbd> to open</span>
                <span><kbd class="font-sans">Esc</kbd> to close</span>
            </div>
        </div>
    </div>
</div>
