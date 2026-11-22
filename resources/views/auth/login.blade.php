<x-guest-layout title="Log in">
    <x-ui.card>
        <h1 class="text-lg font-semibold">Log in to your shop</h1>
        <p class="mt-1 mb-6 text-sm text-ink-muted">Welcome back. Enter your details to continue.</p>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-success-soft px-3 py-2 text-sm text-success" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <x-ui.field label="Email" for="email" name="email">
                <x-ui.input name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />
            </x-ui.field>

            <x-ui.field for="password" name="password">
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="text-sm font-medium">Password</label>
                    <a href="{{ route('password.request') }}" class="text-sm text-brand hover:underline">Forgot password?</a>
                </div>
                <x-ui.input name="password" type="password" required autocomplete="current-password" />
            </x-ui.field>

            <label class="flex items-center gap-2 text-sm text-ink-muted">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded accent-brand"> Remember me
            </label>

            <x-ui.button type="submit" class="w-full">Log in</x-ui.button>
        </form>
    </x-ui.card>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-ink-muted">
            New to BandarOS? <a href="{{ route('register') }}" class="font-medium text-brand hover:underline">Create your shop</a>
        </p>
    @endif
</x-guest-layout>
