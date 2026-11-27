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
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 -top-40 -z-10 mx-auto h-[28rem] max-w-4xl rounded-full bg-brand-soft opacity-70 blur-3xl"></div>

        <div class="mx-auto max-w-6xl px-4 pt-16 pb-12 text-center sm:px-6 sm:pt-24">
            <p class="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3 py-1 text-xs font-medium text-ink-muted">
                <span class="h-1.5 w-1.5 rounded-full bg-brand"></span>
                Inventory software for small shops
            </p>

            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-balance sm:text-6xl">
                Know what’s on your shelves, <span class="text-brand">all the time.</span>
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg text-pretty text-ink-muted">
                BandarOS keeps your products, stock and team in one place. See what’s running low,
                who changed what, and give every staff member just the access they need.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @auth
                    <x-ui.button :href="route('dashboard')" class="px-5 py-2.5 text-base">Go to dashboard</x-ui.button>
                @else
                    <x-ui.button :href="route('register')" class="px-5 py-2.5 text-base">
                        Start free <x-ui.icon name="arrow-right" class="h-4 w-4" />
                    </x-ui.button>
                @endauth
                <x-ui.button :href="route('pricing')" variant="secondary" class="px-5 py-2.5 text-base">See pricing</x-ui.button>
            </div>
            <p class="mt-4 text-sm text-ink-subtle">Free plan, no card needed.</p>
        </div>

        {{-- Product screenshot in a browser frame --}}
        <div class="mx-auto max-w-5xl px-4 pb-20 sm:px-6">
            <div class="overflow-hidden rounded-xl border border-line-strong bg-surface shadow-2xl shadow-brand/10">
                <div class="flex items-center gap-1.5 border-b border-line bg-surface-muted px-4 py-2.5" aria-hidden="true">
                    <span class="h-2.5 w-2.5 rounded-full bg-line-strong"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-line-strong"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-line-strong"></span>
                </div>
                <img src="{{ asset('images/dashboard.jpg') }}" width="800" height="506" class="block w-full dark:hidden"
                     alt="The BandarOS dashboard: product totals, low-stock items, products per category and recent stock changes">
                <img src="{{ asset('images/dashboard-dark.jpg') }}" width="800" height="506" class="hidden w-full dark:block"
                     alt="The BandarOS dashboard in dark mode">
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="scroll-mt-16 border-t border-line bg-surface py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">Everything a shop needs, nothing it doesn’t</h2>
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
            <h2 class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Up and running in three steps</h2>

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
                <h2 class="mt-6 text-3xl font-semibold tracking-tight sm:text-4xl">Security you’d expect from a much bigger company</h2>
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
                <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">Simple pricing</h2>
                <p class="mt-4 text-ink-muted">Start free and upgrade when your shop grows. <a href="{{ route('pricing') }}" class="font-medium text-brand hover:underline">Compare plans</a></p>
            </div>

            @include('marketing.partials.plan-cards')
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-t border-line bg-surface py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Questions and answers</h2>

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
                <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">Get your stock under control today</h2>
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
