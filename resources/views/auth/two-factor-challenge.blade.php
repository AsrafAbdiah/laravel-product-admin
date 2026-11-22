<x-guest-layout title="Two-factor authentication">
    <x-ui.card x-data="{ recovery: false }">
        <h1 class="text-lg font-semibold">Two-factor authentication</h1>
        <p class="mt-1 mb-6 text-sm text-ink-muted" x-show="!recovery">Enter the 6-digit code from your authenticator app.</p>
        <p class="mt-1 mb-6 text-sm text-ink-muted" x-show="recovery" x-cloak>Enter one of your emergency recovery codes.</p>

        <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-5">
            @csrf

            <div x-show="!recovery">
                <x-ui.field label="Code" for="code" name="code">
                    <x-ui.input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus
                                x-bind:disabled="recovery" class="tracking-[0.3em]" />
                </x-ui.field>
            </div>

            <div x-show="recovery" x-cloak>
                <x-ui.field label="Recovery code" for="recovery_code" name="recovery_code">
                    <x-ui.input name="recovery_code" type="text" autocomplete="one-time-code" x-bind:disabled="!recovery" />
                </x-ui.field>
            </div>

            <x-ui.button type="submit" class="w-full">Log in</x-ui.button>
        </form>

        <button type="button" class="mt-4 text-sm text-brand hover:underline" @click="recovery = !recovery"
                x-text="recovery ? 'Use an authentication code' : 'Use a recovery code instead'">Use a recovery code instead</button>
    </x-ui.card>
</x-guest-layout>
