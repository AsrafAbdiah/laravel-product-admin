<x-guest-layout title="Choose a new password">
    <x-ui.card>
        <h1 class="text-lg font-semibold">Choose a new password</h1>
        <p class="mt-1 mb-6 text-sm text-ink-muted">Pick a password you don't use anywhere else.</p>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <x-ui.field label="Email" for="email" name="email">
                <x-ui.input name="email" type="email" :value="old('email', $request->email)" required autocomplete="username" />
            </x-ui.field>

            <x-ui.field label="New password" for="password" name="password" hint="At least 8 characters.">
                <x-ui.input name="password" type="password" required autofocus autocomplete="new-password" />
            </x-ui.field>

            <x-ui.field label="Confirm new password" for="password_confirmation" name="password_confirmation">
                <x-ui.input name="password_confirmation" type="password" required autocomplete="new-password" />
            </x-ui.field>

            <x-ui.button type="submit" class="w-full">Reset password</x-ui.button>
        </form>
    </x-ui.card>
</x-guest-layout>
