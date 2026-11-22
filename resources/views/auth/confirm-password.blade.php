<x-guest-layout title="Confirm password">
    <x-ui.card>
        <h1 class="text-lg font-semibold">Confirm your password</h1>
        <p class="mt-1 mb-6 text-sm text-ink-muted">This is a secure area. Please confirm your password to continue.</p>

        <form method="POST" action="{{ url('/user/confirm-password') }}" class="space-y-5">
            @csrf

            <x-ui.field label="Password" for="password" name="password">
                <x-ui.input name="password" type="password" required autofocus autocomplete="current-password" />
            </x-ui.field>

            <x-ui.button type="submit" class="w-full">Confirm</x-ui.button>
        </form>
    </x-ui.card>
</x-guest-layout>
