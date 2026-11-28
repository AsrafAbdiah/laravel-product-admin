@php
    $features = [
        ['box', 'Inventory', 'Products with photos, categories, prices and stock. Search, filter and sort, restore from the trash, and see the full stock history of every item.'],
        ['users', 'Teams and roles', 'Invite your staff by email and give each person the right role: Owner, Admin, Staff or Viewer. Run more than one shop from one account.'],
        ['shield', 'Security', 'Two-factor login, email verification, session management and a strict security policy in the browser. Every shop’s data is kept apart.'],
        ['bolt', 'Automation', 'Low-stock alerts in the app and by email, a weekly stock summary, and big imports and exports that run in the background.'],
        ['code', 'REST API', 'Connect your other tools with API tokens that are read-only or read-write, rate-limited per shop and documented with Swagger.'],
        ['history', 'Audit log', 'See who changed what and when: prices, stock, roles and invitations, with the before and after values.'],
    ];

    $steps = [
        ['Create your shop', 'Sign up with your email. Your shop is ready in under a minute, on the Free plan.'],
        ['Add your products', 'Type them in one by one, or import your existing list from Excel or CSV.'],
        ['Invite your team', 'Send invitations and pick a role for each person, so everyone sees only what they need.'],
    ];

    $security = [
        'Two-factor login with recovery codes',
        'Data kept separate for every shop',
        'Role-based permissions on every action',
        'Audit log of every change',
        'Content-Security-Policy and secure headers',
        'Per-shop API rate limits',
    ];

    $faqs = [
        ['Is BandarOS really free?', 'Yes. The Free plan has up to '.$plans['free']['max_products'].' products and '.$plans['free']['max_members'].' team members, with no time limit. Pro removes the limits and adds file import.'],
        ['Can my staff see everything?', 'No. Staff can add and edit products, Viewers can only look, and only Owners and Admins can delete items, manage categories, invite people or read the audit log.'],
        ['Is my shop’s data separate from other shops?', 'Yes. Every record belongs to one shop, and every request is checked against your current shop, so another shop’s products are never shown to you, even by direct link.'],
        ['Can I bring my existing product list?', 'Yes. Download the template, fill it in and import it (Pro). Every plan can export to Excel or CSV at any time.'],
        ['What happens when I delete a product?', 'It goes to the trash first, so it can be restored. Only Owners and Admins can delete it permanently.'],
        ['Do you have an API?', 'Yes. Create a token under Account settings and use the REST API. The interactive documentation is at /api/documentation.'],
    ];
@endphp

<x-marketing-layout>
    @push('scripts')
        @vite('resources/js/hero.js')
    @endpush

    {{--
        Hero: a centred stage. Headline above, the shop model in the middle with
        cards floating around it, buttons below. Motion lives in
        resources/js/hero.js; elements marked data-reveal start hidden only when
        JavaScript runs (resources/css/hero.css), so the page is complete without it.
    --}}
    <section data-hero class="relative overflow-hidden border-b border-line">
        <div class="mx-auto flex max-w-7xl flex-col px-4 pt-14 pb-16 sm:px-6 sm:pt-20 lg:px-10">
            {{-- Headline --}}
            <div class="order-1 mx-auto max-w-3xl text-center">
                <p data-reveal="fade" class="flex items-center justify-center gap-3 font-mono text-xs tracking-[0.14em] text-ink-muted uppercase">
                    <span class="text-accent">01</span>
                    <span class="h-px w-8 bg-line-strong" aria-hidden="true"></span>
                    Inventory for small shops
                </p>

                <h1 data-reveal class="font-display-tight mt-7 text-[clamp(2.75rem,6vw,5.25rem)] text-ink">
                    <span class="block overflow-hidden pb-[0.06em]"><span data-hero-line class="block">Every shelf counted.</span></span>
                    <span class="block overflow-hidden pb-[0.06em]"><span data-hero-line class="block text-ink-muted italic">Every change signed.</span></span>
                </h1>

                <p data-reveal="fade" class="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-ink-muted">
                    Stock, staff and an audit trail for shops run by a small team.
                    Know what’s running low before the shelf is empty.
                </p>
            </div>

            {{-- Stage: the shop model, with the cards floating beside it on large screens --}}
            <div data-hero-stage class="relative order-3 mx-auto mt-10 w-full max-w-6xl lg:order-2 lg:mt-6">
                <div data-hero-visual data-reveal class="relative mx-auto w-full max-w-[680px]">
                    <div data-depth="0.15" class="relative">
                        {{-- A hairline "ground" at the level the shop stands on (the image has a 5.7% base margin) --}}
                        <div class="hero-ground pointer-events-none absolute -inset-x-[40%] bottom-[5.7%] h-px bg-line-strong" aria-hidden="true"></div>

                        <img src="{{ asset('images/mini-mart.webp') }}"
                             srcset="{{ asset('images/mini-mart-800.webp') }} 800w, {{ asset('images/mini-mart.webp') }} 1400w"
                             sizes="(min-width: 768px) 680px, 92vw" width="1400" height="979"
                             fetchpriority="high" decoding="async" draggable="false"
                             class="relative block h-auto w-full select-none"
                             alt="A cut-away model of a convenience store with stocked shelves, drinks fridges and a checkout counter">
                    </div>
                </div>

                {{-- Cards: a grid under the model on small screens, floating around it on large ones --}}
                <div class="mt-8 grid gap-3 sm:grid-cols-3 lg:mt-0 lg:block">
                    <div data-depth="0.9" class="lg:absolute lg:top-[12%] lg:left-0 lg:w-64">
                        <div data-reveal="card">
                            <div class="hero-card">
                                <p class="hero-card-label"><span class="h-1.5 w-1.5 rounded-full bg-accent"></span> Low stock · 09:14</p>
                                <p class="mt-2 text-sm font-semibold">Kopi Robusta 1kg</p>
                                <div class="mt-2 flex items-baseline justify-between">
                                    <p class="font-display-tight text-4xl">3 <span class="text-base text-ink-muted">left</span></p>
                                    <p class="font-mono text-[10px] text-ink-subtle uppercase">Reorder at 5</p>
                                </div>
                                <div class="mt-3 h-1 rounded-full bg-surface-muted"><div class="h-1 w-[15%] rounded-full bg-accent"></div></div>
                            </div>
                        </div>
                    </div>

                    <div data-depth="1.3" class="lg:absolute lg:top-[4%] lg:right-0 lg:w-60">
                        <div data-reveal="card">
                            <div class="hero-card">
                                <p class="hero-card-label">Team · Kedai Siti</p>
                                <ul class="mt-3 space-y-2 text-sm">
                                    <li class="flex items-center justify-between gap-3"><span class="font-medium">Siti</span><span class="hero-tag bg-brand-soft text-brand-ink">Owner</span></li>
                                    <li class="flex items-center justify-between gap-3"><span class="font-medium">Amir</span><span class="hero-tag bg-sage-soft text-ink-muted">Staff</span></li>
                                    <li class="flex items-center justify-between gap-3"><span class="font-medium">Mei</span><span class="hero-tag bg-surface-muted text-ink-muted">Viewer</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div data-depth="0.7" class="lg:absolute lg:right-[3%] lg:bottom-[20%] lg:w-72">
                        <div data-reveal="card">
                            <div class="hero-card">
                                <p class="hero-card-label"><span class="h-1.5 w-1.5 rounded-full bg-sage"></span> Audit log</p>
                                <ol class="mt-3 space-y-2.5 text-sm">
                                    <li class="grid grid-cols-[3rem_1fr] gap-2">
                                        <span class="font-mono text-xs text-ink-subtle">09:42</span>
                                        <span><span class="font-medium">Amir</span> changed price <span class="font-mono text-xs whitespace-nowrap">RM 18 → RM 20</span></span>
                                    </li>
                                    <li class="grid grid-cols-[3rem_1fr] gap-2">
                                        <span class="font-mono text-xs text-ink-subtle">09:15</span>
                                        <span><span class="font-medium">Siti</span> restocked Teh Tarik Mix <span class="font-mono text-xs">+24</span></span>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Calls to action: right under the headline on phones, under the model on large screens --}}
            <div data-reveal="fade" class="order-2 mt-9 flex flex-wrap items-center justify-center gap-x-8 gap-y-4 lg:order-3 lg:mt-10">
                {{-- The zone is a little larger than the button, so the pull starts just before the pointer reaches it. --}}
                <span data-magnetic-zone class="-m-4 inline-block p-4">
                    <a data-magnetic href="{{ auth()->check() ? route('dashboard') : route('register') }}"
                       class="inline-flex items-center gap-3 rounded-full bg-brand px-7 py-4 text-[15px] font-semibold text-on-brand transition-colors hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                        <span data-magnetic-label class="inline-flex items-center gap-3">
                            {{ auth()->check() ? 'Go to dashboard' : 'Start free' }}
                            <x-ui.icon name="arrow-right" class="h-4 w-4" />
                        </span>
                    </a>
                </span>
                <a href="{{ route('pricing') }}" class="group inline-flex items-center gap-2 text-[15px] font-medium text-ink">
                    <span class="border-b border-line-strong pb-0.5 transition-colors group-hover:border-ink">See pricing</span>
                </a>
            </div>

            <dl data-reveal="fade" class="order-4 mx-auto mt-12 grid w-full max-w-2xl grid-cols-3 border-t border-line pt-5 text-center font-mono text-[11px] tracking-wide uppercase">
                <div>
                    <dt class="text-ink-subtle">Free plan</dt>
                    <dd class="mt-1 text-ink">{{ $plans['free']['max_products'] }} products</dd>
                </div>
                <div class="border-l border-line">
                    <dt class="text-ink-subtle">Card</dt>
                    <dd class="mt-1 text-ink">Not needed</dd>
                </div>
                <div class="border-l border-line">
                    <dt class="text-ink-subtle">Your data</dt>
                    <dd class="mt-1 text-ink">Kept per shop</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="scroll-mt-16 border-t border-line bg-surface py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-display-tight text-4xl sm:text-5xl">Everything a shop needs, nothing it doesn’t</h2>
                <p class="mt-4 text-ink-muted">The tools larger companies use to run their stock, sized for a shop with a few people.</p>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as [$icon, $name, $text])
                    <div class="rounded-xl border border-line bg-canvas p-6">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-brand-soft text-brand">
                            <x-ui.icon :name="$icon" class="h-5 w-5" />
                        </span>
                        <h3 class="mt-4 font-semibold">{{ $name }}</h3>
                        <p class="mt-2 text-sm leading-6 text-ink-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h2 class="text-center font-display-tight text-4xl sm:text-5xl">Up and running in three steps</h2>

            <ol class="mt-14 grid gap-8 md:grid-cols-3">
                @foreach ($steps as $i => [$name, $text])
                    <li class="flex gap-4 md:flex-col">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-brand font-semibold text-brand tabular-nums">{{ $i + 1 }}</span>
                        <div>
                            <h3 class="font-semibold">{{ $name }}</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Security --}}
    <section class="border-y border-line bg-surface py-20">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2">
            <div>
                <span class="grid h-12 w-12 place-items-center rounded-xl bg-brand-soft text-brand">
                    <x-ui.icon name="lock" class="h-6 w-6" />
                </span>
                <h2 class="mt-6 font-display-tight text-4xl sm:text-5xl">Security you’d expect from a much bigger company</h2>
                <p class="mt-4 text-ink-muted">Your stock list, prices and suppliers are business data. BandarOS protects them the same way enterprise software does, on every plan.</p>
            </div>

            <ul class="grid gap-3 sm:grid-cols-2">
                @foreach ($security as $item)
                    <li class="flex items-start gap-3 rounded-lg border border-line bg-canvas px-4 py-3 text-sm">
                        <x-ui.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-success" />
                        {{ $item }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Pricing teaser --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto mb-14 max-w-2xl text-center">
                <h2 class="font-display-tight text-4xl sm:text-5xl">Simple pricing</h2>
                <p class="mt-4 text-ink-muted">Start free and upgrade when your shop grows. <a href="{{ route('pricing') }}" class="font-medium text-brand hover:underline">Compare plans</a></p>
            </div>

            @include('marketing.partials.plan-cards')
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-t border-line bg-surface py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-center font-display-tight text-4xl sm:text-5xl">Questions and answers</h2>

            <div class="mt-12 divide-y divide-line rounded-xl border border-line bg-canvas">
                @foreach ($faqs as [$question, $answer])
                    <details class="group px-5 py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium [&::-webkit-details-marker]:hidden">
                            {{ $question }}
                            <x-ui.icon name="chevron-right" class="h-4 w-4 shrink-0 text-ink-subtle transition-transform group-open:rotate-90" />
                        </summary>
                        <p class="mt-3 text-sm leading-6 text-ink-muted">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Final call to action --}}
    <section class="py-20">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <div class="rounded-2xl bg-brand px-6 py-14 text-center text-on-brand sm:px-12">
                <h2 class="font-display-tight text-4xl sm:text-5xl">Get your stock under control today</h2>
                <p class="mx-auto mt-4 max-w-xl opacity-90">Set up your shop in a minute. Free for up to {{ $plans['free']['max_products'] }} products.</p>
                <div class="mt-8">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-on-brand px-5 py-2.5 font-medium text-brand hover:opacity-90">Go to dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-on-brand px-5 py-2.5 font-medium text-brand hover:opacity-90">
                            Create your shop <x-ui.icon name="arrow-right" class="h-4 w-4" />
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>
</x-marketing-layout>
