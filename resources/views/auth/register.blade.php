<x-guest-layout title="Create your shop">
    <x-ui.card>
        <h1 class="text-lg font-semibold">Create your shop</h1>
        <p class="mt-1 mb-6 text-sm text-ink-muted">Start managing your products and stock in minutes.</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <x-ui.field label="Your name" for="name" name="name">
                <x-ui.input name="name" type="text" :value="old('name')" required autofocus autocomplete="name" />
            </x-ui.field>

            <x-ui.field label="Shop name" for="shop_name" name="shop_name" hint="You can change this later.">
                <x-ui.input name="shop_name" type="text" :value="old('shop_name')" required maxlength="100" placeholder="e.g. Kedai Runcit Ali" />
            </x-ui.field>

            <x-ui.field label="Email" for="email" name="email">
                <x-ui.input name="email" type="email" :value="old('email')" required autocomplete="username" />
            </x-ui.field>

            <x-ui.field label="Password" for="password" name="password" hint="At least 8 characters.">
                <x-ui.input name="password" type="password" required autocomplete="new-password" />
            </x-ui.field>

            <x-ui.field label="Confirm password" for="password_confirmation" name="password_confirmation">
                <x-ui.input name="password_confirmation" type="password" required autocomplete="new-password" />
            </x-ui.field>

            <x-ui.button type="submit" class="w-full">Create shop</x-ui.button>
        </form>
    </x-ui.card>

    <p class="mt-6 text-center text-sm text-ink-muted">
        Already have an account? <a href="{{ route('login') }}" class="font-medium text-brand hover:underline">Log in</a>
    </p>
</x-guest-layout>
