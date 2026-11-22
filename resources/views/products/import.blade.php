<x-layout title="Import products">
    <div class="mx-auto max-w-2xl">
        <x-ui.page-header title="Import products"
                          description="Add many products at once from an Excel or CSV file. Valid rows are imported; rows with a problem are skipped and listed so you can fix them." />

        @if ($result)
            <x-ui.card class="mb-6 {{ $result['failed'] ? 'border-warning/50' : 'border-success/40' }}">
                <div class="flex items-start gap-3" aria-live="polite">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $result['failed'] ? 'bg-warning-soft text-warning' : 'bg-success-soft text-success' }}">
                        <x-ui.icon :name="$result['failed'] ? 'alert' : 'check'" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold">
                            {{ $result['imported'] }} product(s) imported{{ $result['failed'] ? ', '.count($result['failed']).' row(s) skipped' : '' }}.
                        </h2>
                        @if (! $result['failed'] && $result['imported'] > 0)
                            <a href="{{ route('products.index') }}" class="mt-1 inline-block text-sm font-medium text-brand hover:underline">View products</a>
                        @endif
                    </div>
                </div>

                @if ($result['failed'])
                    <div class="mt-4 overflow-x-auto rounded-lg border border-line">
                        <table class="min-w-full divide-y divide-line text-sm">
                            <thead class="bg-surface-muted text-left text-xs font-semibold tracking-wide text-ink-muted uppercase">
                                <tr>
                                    <th class="px-3 py-2">Row</th>
                                    <th class="px-3 py-2">Problem</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($result['failed'] as $failure)
                                    <tr>
                                        <td class="px-3 py-2 align-top font-medium tabular-nums">{{ $failure['row'] }}</td>
                                        <td class="px-3 py-2">
                                            @foreach ($failure['errors'] as $error)
                                                <p>{{ $error }}</p>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        @endif

        <form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data">
            @csrf
            <x-ui.card>
                <div class="space-y-5">
                    <x-ui.field label="File" for="file" name="file" hint=".xlsx or .csv, up to 5 MB." required>
                        <input id="file" name="file" type="file" required accept=".xlsx,.csv"
                               class="block w-full text-sm text-ink-muted file:mr-3 file:rounded-lg file:border-0 file:bg-surface-muted file:px-3 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:bg-line">
                    </x-ui.field>

                    <div class="rounded-lg bg-surface-muted p-4 text-sm text-ink-muted">
                        <p class="mb-2 font-medium text-ink">Expected columns (first row = headings)</p>
                        <ul class="list-inside list-disc space-y-1">
                            <li><strong class="text-ink">Name</strong>: required</li>
                            <li><strong class="text-ink">Category</strong>: the name of an existing category (not case-sensitive)</li>
                            <li><strong class="text-ink">Description</strong>: optional</li>
                            <li><strong class="text-ink">Price</strong>: e.g. 49.90, up to 2 decimals</li>
                            <li><strong class="text-ink">Stock</strong>: a whole number, 0 or more</li>
                            <li><strong class="text-ink">Status</strong>: enabled or disabled (blank means enabled)</li>
                        </ul>
                        <p class="mt-2">Files from "Export" use the same columns, so they can be edited and imported again as new products.</p>
                    </div>
                </div>
            </x-ui.card>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <x-ui.button variant="ghost" :href="route('products.import.template')" icon="download">Download template</x-ui.button>
                <x-ui.button type="submit" icon="upload">Import</x-ui.button>
            </div>
        </form>
    </div>
</x-layout>
