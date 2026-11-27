<x-layout title="Account settings">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <x-ui.page-header title="Account settings" description="Your profile, password and sign-in security." />
            @include('settings._tabs')
        </div>

        {{-- Profile --}}
        <x-ui.card title="Profile">
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.field label="Name" for="name">
                        <x-ui.input name="name" type="text" :value="old('name', $user->name)" required autocomplete="name"
                                    :invalid="$errors->updateProfileInformation->has('name')" />
                        @if ($errors->updateProfileInformation->has('name'))
                            <p class="mt-1.5 text-sm text-danger">{{ $errors->updateProfileInformation->first('name') }}</p>
                        @endif
                    </x-ui.field>

                    <x-ui.field label="Email" for="email" hint="Changing it means verifying the new address.">
                        <x-ui.input name="email" type="email" :value="old('email', $user->email)" required autocomplete="username"
                                    :invalid="$errors->updateProfileInformation->has('email')" />
                        @if ($errors->updateProfileInformation->has('email'))
                            <p class="mt-1.5 text-sm text-danger">{{ $errors->updateProfileInformation->first('email') }}</p>
                        @endif
                    </x-ui.field>
                </div>

                <div class="flex items-center justify-end gap-3">
                    @if (session('status') === 'profile-information-updated')
                        <span class="text-sm text-success" role="status">Saved.</span>
                    @endif
                    <x-ui.button type="submit">Save profile</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        {{-- Password --}}
        <x-ui.card title="Password">
            <form method="POST" action="{{ route('user-password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-ui.field label="Current password" for="current_password">
                    <x-ui.input name="current_password" type="password" required autocomplete="current-password"
                                :invalid="$errors->updatePassword->has('current_password')" />
                    @if ($errors->updatePassword->has('current_password'))
                        <p class="mt-1.5 text-sm text-danger">{{ $errors->updatePassword->first('current_password') }}</p>
                    @endif
                </x-ui.field>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.field label="New password" for="new_password" hint="At least 8 characters.">
                        <x-ui.input name="password" id="new_password" type="password" required autocomplete="new-password"
                                    :invalid="$errors->updatePassword->has('password')" />
                        @if ($errors->updatePassword->has('password'))
                            <p class="mt-1.5 text-sm text-danger">{{ $errors->updatePassword->first('password') }}</p>
                        @endif
                    </x-ui.field>

                    <x-ui.field label="Confirm new password" for="password_confirmation">
                        <x-ui.input name="password_confirmation" type="password" required autocomplete="new-password" />
                    </x-ui.field>
                </div>

                <div class="flex items-center justify-end gap-3">
                    @if (session('status') === 'password-updated')
                        <span class="text-sm text-success" role="status">Password changed.</span>
                    @endif
                    <x-ui.button type="submit">Change password</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        {{-- Two-factor authentication --}}
        <x-ui.card title="Two-factor authentication" id="two-factor" class="scroll-mt-20">
            <x-slot:actions>
                @if ($user->hasEnabledTwoFactorAuthentication())
                    <x-ui.badge tone="success">On</x-ui.badge>
                @else
                    <x-ui.badge>Off</x-ui.badge>
                @endif
            </x-slot:actions>

            <p class="text-sm text-ink-muted">
                Protect your account with a second step: a 6-digit code from an authenticator app
                (Google Authenticator, Microsoft Authenticator, 1Password...) every time you log in.
            </p>

            @if (! $user->two_factor_secret)
                {{-- Off --}}
                <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-5">
                    @csrf
                    <x-ui.button type="submit">Turn on two-factor</x-ui.button>
                </form>
            @elseif (! $user->two_factor_confirmed_at)
                {{-- Set up, waiting for the first code --}}
                <div class="mt-5 space-y-5 rounded-lg border border-line p-5">
                    <p class="text-sm font-medium">1. Scan this QR code with your authenticator app</p>
                    <div class="inline-block rounded-lg bg-white p-3">{!! $user->twoFactorQrCodeSvg() !!}</div>
                    <p class="text-sm text-ink-muted">
                        Can't scan it? Enter this key instead:
                        <code class="rounded bg-surface-muted px-1.5 py-0.5 font-mono text-ink">{{ decrypt($user->two_factor_secret) }}</code>
                    </p>

                    <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">
                        @csrf
                        <x-ui.field label="2. Enter the 6-digit code it shows" for="code">
                            <x-ui.input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required class="max-w-48 tracking-[0.3em]"
                                        :invalid="$errors->confirmTwoFactorAuthentication->has('code')" />
                            @if ($errors->confirmTwoFactorAuthentication->has('code'))
                                <p class="mt-1.5 text-sm text-danger">{{ $errors->confirmTwoFactorAuthentication->first('code') }}</p>
                            @endif
                        </x-ui.field>
                        <div class="flex gap-2">
                            <x-ui.button type="submit">Confirm and turn on</x-ui.button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('two-factor.disable') }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="ghost" size="sm">Cancel setup</x-ui.button>
                    </form>
                </div>
            @else
                {{-- On --}}
                @if (in_array(session('status'), ['two-factor-authentication-confirmed', 'recovery-codes-generated'], true))
                    <div class="mt-5 rounded-lg border border-warning/50 bg-warning-soft p-4">
                        <p class="text-sm font-medium text-warning">Save these recovery codes somewhere safe.</p>
                        <p class="mt-1 text-sm text-ink-muted">Each one can be used once to log in if you lose your phone. They won't be shown again.</p>
                        <ul class="mt-3 grid gap-1 font-mono text-sm sm:grid-cols-2">
                            @foreach ($user->recoveryCodes() as $code)
                                <li class="rounded bg-surface px-2 py-1">{{ $code }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-5 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary">New recovery codes</x-ui.button>
                    </form>
                    <form method="POST" action="{{ route('two-factor.disable') }}"
                          data-confirm="Turn off two-factor authentication? Your account will only be protected by your password."
                          data-confirm-title="Turn off two-factor" data-confirm-label="Turn off">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="ghost" class="hover:text-danger">Turn off</x-ui.button>
                    </form>
                </div>
            @endif
        </x-ui.card>
    </div>
</x-layout>
