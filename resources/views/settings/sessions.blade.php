<x-layout title="Sessions">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <x-ui.page-header title="Account settings" description="Where your account is signed in." />
            @include('settings._tabs')
        </div>

        <x-ui.card title="Active sessions" :padding="false">
            @if ($sessions->isEmpty())
                <x-ui.empty-state icon="monitor" title="No session records."
                                  description="Sessions are listed when the app stores them in the database (SESSION_DRIVER=database)." />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($sessions as $session)
                        <li class="flex items-center gap-3 px-5 py-3.5">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-surface-muted text-ink-muted">
                                <x-ui.icon name="monitor" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $session->device }}</p>
                                <p class="text-xs text-ink-muted">
                                    {{ $session->ip ?? 'Unknown IP' }} ·
                                    @if ($session->isCurrent)
                                        <span class="font-medium text-success">This device</span>
                                    @else
                                        active {{ $session->lastActive->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Log out other devices">
            <p class="text-sm text-ink-muted">
                Signed in somewhere you don't recognise, or on a shared computer? Log out everywhere except here.
                For safety, confirm with your password.
            </p>
            <form method="POST" action="{{ route('settings.sessions.destroy') }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                @method('DELETE')
                <x-ui.field label="Password" for="logout-password" class="min-w-0 flex-1">
                    <x-ui.input name="password" id="logout-password" type="password" required autocomplete="current-password"
                                :invalid="$errors->logoutOtherSessions->has('password')" />
                    @if ($errors->logoutOtherSessions->has('password'))
                        <p class="mt-1.5 text-sm text-danger">{{ $errors->logoutOtherSessions->first('password') }}</p>
                    @endif
                </x-ui.field>
                <x-ui.button type="submit" variant="secondary">Log out other devices</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layout>
