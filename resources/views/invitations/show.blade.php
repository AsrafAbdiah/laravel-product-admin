<x-guest-layout title="Invitation">
    <x-ui.card>
        <div class="mb-4 grid h-10 w-10 place-items-center rounded-full bg-brand-soft text-brand-ink">
            <x-ui.icon name="users" />
        </div>

        <h1 class="text-lg font-semibold">Join {{ $invitation->team->name }}</h1>
        <p class="mt-1 text-sm text-ink-muted">
            {{ $invitation->inviter?->name ?? 'Someone' }} invited you to join as
            <strong class="text-ink">{{ $invitation->role }}</strong>.
        </p>

        @if ($emailMatches)
            <form method="POST" action="{{ route('invitations.accept', $token) }}" class="mt-6">
                @csrf
                <x-ui.button type="submit" class="w-full">Accept and join</x-ui.button>
            </form>
        @else
            <p class="mt-4 rounded-lg bg-warning-soft px-3 py-2 text-sm text-warning" role="alert">
                This invitation was sent to <strong>{{ $invitation->email }}</strong>, but you're logged in as
                <strong>{{ auth()->user()->email }}</strong>. Log in with the invited address to accept it.
            </p>
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <x-ui.button type="submit" variant="secondary" class="w-full">Log out</x-ui.button>
            </form>
        @endif
    </x-ui.card>

    <p class="mt-6 text-center text-sm text-ink-muted">
        <a href="{{ route('dashboard') }}" class="font-medium text-brand hover:underline">Go to my dashboard</a>
    </p>
</x-guest-layout>
