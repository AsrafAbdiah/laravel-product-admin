<x-guest-layout title="Verify your email">
    <x-ui.card>
        <div class="mb-4 grid h-10 w-10 place-items-center rounded-full bg-brand-soft text-brand-ink">
            <x-ui.icon name="check" />
        </div>
        <h1 class="text-lg font-semibold">Check your inbox</h1>
        <p class="mt-1 text-sm text-ink-muted">
            We sent a verification link to <strong class="text-ink">{{ auth()->user()->email }}</strong>.
            Click it to finish setting up your shop.
        </p>

        @if (session('status') === 'verification-link-sent')
            <p class="mt-4 rounded-lg bg-success-soft px-3 py-2 text-sm text-success" role="status">A new verification link has been sent.</p>
        @endif

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit">Resend email</x-ui.button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button type="submit" variant="ghost">Log out</x-ui.button>
            </form>
        </div>
    </x-ui.card>
</x-guest-layout>
