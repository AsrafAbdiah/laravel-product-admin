{{-- One card per plan in config/bandaros.php. Expects $plans from MarketingController. --}}
<div class="mx-auto grid max-w-4xl gap-6 md:grid-cols-2">
    @foreach ($plans as $key => $plan)
        @php $featured = $key === 'pro'; @endphp
        <div @class([
            'relative flex flex-col rounded-2xl border bg-surface p-6 shadow-xs sm:p-8',
            'border-brand ring-1 ring-brand' => $featured,
            'border-line' => ! $featured,
        ])>
            @if ($featured)
                <span class="absolute -top-3 left-6 rounded-full bg-brand px-3 py-1 text-xs font-semibold text-on-brand">Free during beta</span>
            @endif

            <h3 class="text-lg font-semibold">{{ $plan['name'] }}</h3>
            <p class="mt-1 text-sm text-ink-muted">{{ $plan['tagline'] }}</p>

            <p class="mt-6 flex items-baseline gap-2">
                <span class="text-4xl font-semibold tracking-tight">{{ $plan['price'] }}</span>
                <span class="text-sm text-ink-subtle">{{ $plan['period'] }}</span>
            </p>

            <ul class="mt-6 flex-1 space-y-3 text-sm">
                @foreach ($plan['features'] as $feature)
                    <li class="flex gap-3">
                        <x-ui.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">
                @auth
                    <x-ui.button :href="route('team.show')" :variant="$featured ? 'primary' : 'secondary'" class="w-full">Manage your plan</x-ui.button>
                @else
                    <x-ui.button :href="route('register')" :variant="$featured ? 'primary' : 'secondary'" class="w-full">
                        {{ $featured ? 'Start free, upgrade anytime' : 'Start free' }}
                    </x-ui.button>
                @endauth
            </div>
        </div>
    @endforeach
</div>
