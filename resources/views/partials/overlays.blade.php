@php
    // Flash messages become toasts. Bulk-delete validation errors have no
    // form field to sit under, so they are shown as an error toast too.
    $toasts = collect([
        session('success') ? ['type' => 'success', 'message' => session('success')] : null,
        session('error') ? ['type' => 'error', 'message' => session('error')] : null,
        $errors->has('ids') || $errors->has('ids.*')
            ? ['type' => 'error', 'message' => $errors->first('ids') ?: $errors->first('ids.*')]
            : null,
    ])->filter()->values();
@endphp

{{-- Toasts --}}
<div x-data="toasts(@js($toasts))" class="pointer-events-none fixed inset-x-0 top-0 z-50 flex flex-col items-end gap-2 p-4 sm:top-2 sm:right-2 sm:left-auto"
     style="padding-top: max(1rem, env(safe-area-inset-top))" aria-live="polite">
    <template x-for="toast in items" :key="toast.id">
        <div x-show="toast.visible" x-transition.opacity.duration.200ms
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-surface p-4 text-sm shadow-lg"
             :class="toast.type === 'error' ? 'border-danger/40' : 'border-line'"
             :role="toast.type === 'error' ? 'alert' : 'status'">
            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full"
                  :class="toast.type === 'error' ? 'bg-danger-soft text-danger' : 'bg-success-soft text-success'">
                <template x-if="toast.type === 'error'"><x-ui.icon name="alert" class="h-3.5 w-3.5" /></template>
                <template x-if="toast.type !== 'error'"><x-ui.icon name="check" class="h-3.5 w-3.5" /></template>
            </span>
            <p class="min-w-0 flex-1 text-ink" x-text="toast.message"></p>
            <button type="button" class="text-ink-subtle hover:text-ink" @click="remove(toast.id)" aria-label="Dismiss">
                <x-ui.icon name="x" class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>

{{-- Confirmation dialog for forms with data-confirm --}}
<div x-data="confirmDialog" @confirm.window="show($event.detail)" @keydown.escape.window="cancel()">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/50" @click="cancel()"></div>
        <div x-show="open" x-transition role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message"
             class="relative w-full max-w-md rounded-xl border border-line bg-surface p-6 shadow-xl">
            <div class="flex gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-danger-soft text-danger">
                    <x-ui.icon name="alert" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <h2 id="confirm-title" class="font-semibold text-ink" x-text="title"></h2>
                    <p id="confirm-message" class="mt-1 text-sm text-ink-muted" x-text="message"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-ui.button variant="secondary" @click="cancel()">Cancel</x-ui.button>
                <x-ui.button variant="danger" x-ref="confirmButton" @click="confirm()"><span x-text="confirmLabel"></span></x-ui.button>
            </div>
        </div>
    </div>
</div>
