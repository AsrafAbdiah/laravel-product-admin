<x-marketing-layout title="Pricing" description="BandarOS pricing: a Free plan for new shops and a Pro plan with unlimited products, team members and file import.">
    <section class="mx-auto max-w-6xl px-4 pt-16 pb-20 sm:px-6 sm:pt-24">
        <div class="mx-auto mb-14 max-w-2xl text-center">
            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">Pricing that grows with your shop</h1>
            <p class="mt-4 text-lg text-ink-muted">Start on the Free plan. Upgrade when you need more products, more people or file import.</p>
        </div>

        @include('marketing.partials.plan-cards')

        <p class="mx-auto mt-6 max-w-2xl text-center text-sm text-ink-subtle">
            BandarOS is in beta: payments aren’t connected yet, so the shop owner can switch to Pro for free on the Team page.
        </p>
    </section>

    {{-- Feature comparison, built from the same plan settings the app enforces --}}
    <section class="border-t border-line bg-surface py-20">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <h2 class="text-center text-2xl font-semibold tracking-tight sm:text-3xl">Compare plans</h2>

            <div class="mt-10 overflow-x-auto rounded-xl border border-line">
                <table class="w-full text-sm">
                    <thead class="bg-surface-muted text-left">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium text-ink-muted">Feature</th>
                            @foreach ($plans as $plan)
                                <th scope="col" class="w-28 px-4 py-3 text-center font-semibold sm:w-36">{{ $plan['name'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($comparison as $feature => $values)
                            <tr>
                                <th scope="row" class="px-4 py-3 text-left font-normal">{{ $feature }}</th>
                                @foreach ($values as $value)
                                    <td class="px-4 py-3 text-center">
                                        @if ($value === true)
                                            <x-ui.icon name="check" class="mx-auto h-5 w-5 text-brand" />
                                            <span class="sr-only">Included</span>
                                        @elseif ($value === false)
                                            <span class="text-ink-subtle" aria-hidden="true">–</span>
                                            <span class="sr-only">Not included</span>
                                        @else
                                            <span class="font-medium tabular-nums">{{ $value }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="py-20">
        <div class="mx-auto grid max-w-4xl gap-8 px-4 sm:grid-cols-2 sm:px-6">
            <div>
                <h3 class="font-semibold">What counts as a team member?</h3>
                <p class="mt-2 text-sm leading-6 text-ink-muted">Everyone in your shop, including you, plus anyone you’ve invited who hasn’t joined yet. An open invitation holds a seat.</p>
            </div>
            <div>
                <h3 class="font-semibold">What if I go over a Free limit?</h3>
                <p class="mt-2 text-sm leading-6 text-ink-muted">Nothing is deleted. You just can’t add more products or people until you upgrade or make room.</p>
            </div>
            <div>
                <h3 class="font-semibold">Can I move back to Free?</h3>
                <p class="mt-2 text-sm leading-6 text-ink-muted">Yes, the shop owner can change the plan at any time on the Team page.</p>
            </div>
            <div>
                <h3 class="font-semibold">Is my data mine?</h3>
                <p class="mt-2 text-sm leading-6 text-ink-muted">Yes. Export your whole product list to Excel or CSV whenever you like, on any plan.</p>
            </div>
        </div>
    </section>
</x-marketing-layout>
