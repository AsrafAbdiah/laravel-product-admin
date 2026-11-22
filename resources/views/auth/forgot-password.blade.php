<x-guest-layout title="Forgot password">
    <x-ui.card>
        <h1 class="text-lg font-semibold">Forgot your password?</h1>
        <p class="mt-1 mb-6 text-sm text-ink-muted">Enter your email and we'll send you a link to choose a new one.</p>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-success-soft px-3 py-2 text-sm text-success" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <x-ui.field label="Email" for="email" name="email">
                <x-ui.input name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />
            </x-ui.field>

            <x-ui.button type="submit" class="w-full">Email reset link</x-ui.button>
        </form>
    </x-ui.card>

    <p class="mt-6 text-center text-sm text-ink-muted">
        <a href="{{ route('login') }}" class="font-medium text-brand hover:underline">Back to log in</a>
    </p>
</x-guest-layout>
