<x-guest-layout title="Log in">
    <x-ui.card>
        <h1 class="text-lg font-semibold">Log in to your shop</h1>
        <p class="mt-1 mb-6 text-sm text-ink-muted">Welcome back. Enter your details to continue.</p>

        <form method="POST" action="{{ url('/login') }}" class="space-y-5">
            @csrf

            <x-ui.field label="Email" for="email" name="email">
                <x-ui.input name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />
            </x-ui.field>

            <x-ui.field label="Password" for="password" name="password">
                <x-ui.input name="password" type="password" required autocomplete="current-password" />
            </x-ui.field>

            <label class="flex items-center gap-2 text-sm text-ink-muted">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded accent-brand"> Remember me
            </label>

            <x-ui.button type="submit" class="w-full">Log in</x-ui.button>
        </form>
    </x-ui.card>
</x-guest-layout>
