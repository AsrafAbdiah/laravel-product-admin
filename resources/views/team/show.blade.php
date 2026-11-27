@php
    use App\Support\Roles;
    $canManage = auth()->user()->can('manageMembers', $team);
    $roleTones = [Roles::OWNER => 'brand', Roles::ADMIN => 'success', Roles::STAFF => 'neutral', Roles::VIEWER => 'neutral'];
@endphp

<x-layout title="Team">
    <div class="mx-auto max-w-4xl space-y-6">
        <x-ui.page-header title="Team" :description="$members->count().' '.Str::plural('member', $members->count()).' in '.$team->name" />

        {{-- Shop name --}}
        @if ($canManage)
            <x-ui.card title="Shop">
                <form method="POST" action="{{ route('team.update') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    @method('PUT')
                    <x-ui.field label="Shop name" for="name" name="name" class="min-w-0 flex-1">
                        <x-ui.input name="name" type="text" :value="old('name', $team->name)" required maxlength="100" />
                    </x-ui.field>
                    <x-ui.button type="submit" variant="secondary">Save</x-ui.button>
                </form>
            </x-ui.card>
        @endif

        {{-- Plan and usage --}}
        <x-ui.card title="Plan">
            <x-slot:actions>
                <x-ui.badge :tone="$team->plan === 'pro' ? 'brand' : 'neutral'">{{ $plans[$team->plan]['name'] }}</x-ui.badge>
            </x-slot:actions>

            <div class="grid gap-5 sm:grid-cols-2">
                @foreach (['products' => 'Products', 'members' => 'Members & invitations'] as $key => $label)
                    @php
                        ['used' => $used, 'max' => $max] = $usage[$key];
                        $percent = $max ? min(100, round($used / $max * 100)) : 0;
                    @endphp
                    <div>
                        <div class="mb-1.5 flex justify-between text-sm">
                            <span class="text-ink-muted">{{ $label }}</span>
                            <span class="font-medium tabular-nums">{{ $used }} / {{ $max ?? 'Unlimited' }}</span>
                        </div>
                        @if ($max)
                            <div class="h-2 rounded-full bg-surface-muted" aria-hidden="true">
                                <div class="h-2 rounded-full {{ $percent >= 100 ? 'bg-danger' : ($percent >= 80 ? 'bg-warning' : 'bg-brand') }}" style="width: {{ $percent }}%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ($plans as $key => $plan)
                    <div @class(['rounded-lg border p-4', 'border-brand bg-brand-soft/40' => $team->plan === $key, 'border-line' => $team->plan !== $key])>
                        <p class="font-semibold">{{ $plan['name'] }}</p>
                        <ul class="mt-2 space-y-1 text-sm text-ink-muted">
                            <li>{{ $plan['max_products'] ? 'Up to '.$plan['max_products'].' products' : 'Unlimited products' }}</li>
                            <li>{{ $plan['max_members'] ? 'Up to '.$plan['max_members'].' members' : 'Unlimited members' }}</li>
                            <li>{{ $plan['import'] ? 'Import from Excel / CSV' : 'No file import' }}</li>
                        </ul>
                        @can('managePlan', $team)
                            @if ($team->plan !== $key)
                                <form method="POST" action="{{ route('team.plan.update') }}" class="mt-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="plan" value="{{ $key }}">
                                    <x-ui.button type="submit" size="sm" :variant="$key === 'pro' ? 'primary' : 'secondary'">Switch to {{ $plan['name'] }}</x-ui.button>
                                </form>
                            @endif
                        @endcan
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-ink-subtle">Payments aren't connected yet, so switching plans is free.</p>
        </x-ui.card>

        {{-- Members --}}
        <x-ui.card title="Members" :padding="false">
            <ul class="divide-y divide-line">
                @foreach ($members as $member)
                    @php $role = $roles[$member->id] ?? null; @endphp
                    <li class="flex flex-wrap items-center gap-3 px-5 py-3.5">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-soft text-sm font-semibold text-brand-ink">
                            {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ $member->name }}
                                @if ($member->is(auth()->user())) <span class="text-xs font-normal text-ink-subtle">(you)</span> @endif
                            </p>
                            <p class="truncate text-xs text-ink-muted">{{ $member->email }}</p>
                        </div>

                        @can('changeMember', [$team, $member])
                            <form method="POST" action="{{ route('team.members.update', $member) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <x-ui.select name="role" id="role-{{ $member->id }}" aria-label="Role for {{ $member->name }}" class="py-1.5"
                                             x-data @change="$el.form.requestSubmit()">
                                    @foreach (Roles::assignable() as $option)
                                        <option value="{{ $option }}" @selected($role === $option)>{{ ucfirst($option) }}</option>
                                    @endforeach
                                </x-ui.select>
                                <noscript><x-ui.button type="submit" size="sm" variant="secondary">Save</x-ui.button></noscript>
                            </form>
                            <form method="POST" action="{{ route('team.members.destroy', $member) }}"
                                  data-confirm="Remove {{ $member->name }} from {{ $team->name }}? They will lose access to this shop."
                                  data-confirm-title="Remove member" data-confirm-label="Remove">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" class="hover:text-danger">Remove</x-ui.button>
                            </form>
                        @else
                            <x-ui.badge :tone="$roleTones[$role] ?? 'neutral'">{{ $role ? ucfirst($role) : 'No role' }}</x-ui.badge>
                        @endcan
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        {{-- Invitations --}}
        @if ($canManage)
            <x-ui.card title="Invite someone" id="invite" class="scroll-mt-20">
                <form method="POST" action="{{ route('team.invitations.store') }}" class="grid gap-3 sm:grid-cols-[1fr_10rem_auto] sm:items-end">
                    @csrf
                    <x-ui.field label="Email" for="invite-email" name="email">
                        <x-ui.input name="email" id="invite-email" type="email" :value="old('email')" required placeholder="name@example.com" />
                    </x-ui.field>
                    <x-ui.field label="Role" for="invite-role" name="role">
                        <x-ui.select name="role" id="invite-role">
                            @foreach (Roles::assignable() as $option)
                                <option value="{{ $option }}" @selected(old('role', Roles::STAFF) === $option)>{{ ucfirst($option) }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.button type="submit" icon="mail">Send invite</x-ui.button>
                </form>

                @if ($invitations->isNotEmpty())
                    <h3 class="mt-6 mb-2 text-sm font-semibold text-ink-muted">Pending invitations</h3>
                    <ul class="divide-y divide-line rounded-lg border border-line">
                        @foreach ($invitations as $invitation)
                            <li class="flex flex-wrap items-center gap-3 px-4 py-2.5 text-sm">
                                <x-ui.icon name="mail" class="h-4 w-4 text-ink-subtle" />
                                <span class="min-w-0 flex-1 truncate">{{ $invitation->email }}</span>
                                <x-ui.badge>{{ ucfirst($invitation->role) }}</x-ui.badge>
                                <span class="text-xs {{ $invitation->isExpired() ? 'text-danger' : 'text-ink-subtle' }}">
                                    {{ $invitation->isExpired() ? 'Expired' : 'Expires '.$invitation->expires_at->diffForHumans() }}
                                </span>
                                <form method="POST" action="{{ route('team.invitations.destroy', $invitation) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm">Cancel</x-ui.button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        @endif

        {{-- What each role can do --}}
        <x-ui.card title="Roles">
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach (Roles::DESCRIPTIONS as $roleName => $description)
                    <div>
                        <dt><x-ui.badge :tone="$roleTones[$roleName]">{{ ucfirst($roleName) }}</x-ui.badge></dt>
                        <dd class="mt-1 text-sm text-ink-muted">{{ $description }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-ui.card>
    </div>
</x-layout>
