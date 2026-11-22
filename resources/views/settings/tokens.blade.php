<x-layout title="API tokens">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <x-ui.page-header title="Account settings" description="Connect other apps and scripts to BandarOS through the API." />
            @include('settings._tabs')
        </div>

        @if ($plainToken)
            <x-ui.card class="border-warning/50">
                <p class="text-sm font-medium text-warning">Copy your new token now. For your security it won't be shown again.</p>
                <div class="mt-3 flex gap-2" x-data="{ copied: false }">
                    <input type="text" readonly value="{{ $plainToken }}" aria-label="New API token" x-ref="token"
                           class="min-w-0 flex-1 rounded-lg border border-line-strong bg-surface-muted px-3 py-2 font-mono text-sm" @focus="$el.select()">
                    <x-ui.button variant="secondary"
                                 @click="navigator.clipboard.writeText($refs.token.value).then(() => copied = true).catch(() => $refs.token.select())">
                        <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                    </x-ui.button>
                </div>
                <p class="mt-2 text-xs text-ink-muted">Send it as <code class="font-mono">Authorization: Bearer &lt;token&gt;</code>.</p>
            </x-ui.card>
        @endif

        <x-ui.card title="Create a token">
            <form method="POST" action="{{ route('settings.tokens.store') }}" class="space-y-5">
                @csrf
                <x-ui.field label="Name" for="token-name" name="name" hint="Something to recognise it by, e.g. &quot;Stock sync script&quot;.">
                    <x-ui.input name="name" id="token-name" type="text" :value="old('name')" required maxlength="100" />
                </x-ui.field>

                <fieldset>
                    <legend class="mb-1.5 text-sm font-medium">Access</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (['read' => ['Read only', 'Can list and view data and export. Cannot change anything.'], 'write' => ['Read & write', 'Can also create, edit and delete, as far as your role allows.']] as $value => [$label, $help])
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-checked:border-brand has-checked:bg-brand-soft/40">
                                <input type="radio" name="access" value="{{ $value }}" class="mt-0.5 accent-brand" @checked(old('access', 'read') === $value)>
                                <span>
                                    <span class="block text-sm font-medium">{{ $label }}</span>
                                    <span class="block text-xs text-ink-muted">{{ $help }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('access') <p class="mt-1.5 text-sm text-danger">{{ $message }}</p> @enderror
                </fieldset>

                <div class="flex justify-end">
                    <x-ui.button type="submit">Create token</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Your tokens" :padding="false">
            @if ($tokens->isEmpty())
                <x-ui.empty-state icon="code" title="No tokens yet." description="Tokens let scripts and other apps use the API as you." />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($tokens as $token)
                        <li class="flex flex-wrap items-center gap-3 px-5 py-3.5">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $token->name }}</p>
                                <p class="text-xs text-ink-muted">
                                    Created {{ $token->created_at->diffForHumans() }} ·
                                    {{ $token->last_used_at ? 'last used '.$token->last_used_at->diffForHumans() : 'never used' }}
                                </p>
                            </div>
                            @if (in_array('*', $token->abilities, true))
                                <x-ui.badge tone="brand">Full access</x-ui.badge>
                            @elseif ($token->can('write'))
                                <x-ui.badge tone="success">Read & write</x-ui.badge>
                            @else
                                <x-ui.badge>Read only</x-ui.badge>
                            @endif
                            <form method="POST" action="{{ route('settings.tokens.destroy', $token->id) }}"
                                  data-confirm="Revoke &quot;{{ $token->name }}&quot;? Anything using it will stop working."
                                  data-confirm-title="Revoke token" data-confirm-label="Revoke">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" class="hover:text-danger">Revoke</x-ui.button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</x-layout>
