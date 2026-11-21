<x-layout title="Import Products">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('products.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back to products</a>
        <h1 class="mt-2 mb-2 text-2xl font-semibold text-slate-900">Import Products</h1>
        <p class="mb-6 text-sm text-slate-600">
            Add many products at once from an Excel or CSV file. Valid rows are imported, and any row with a problem is skipped and listed below so you can fix it.
        </p>

        @if ($result)
            <section class="mb-6 rounded-lg border {{ $result['failed'] ? 'border-amber-200 bg-amber-50' : 'border-green-200 bg-green-50' }} p-4" aria-live="polite">
                <h2 class="font-semibold {{ $result['failed'] ? 'text-amber-900' : 'text-green-900' }}">
                    {{ $result['imported'] }} product(s) imported{{ $result['failed'] ? ', '.count($result['failed']).' row(s) skipped' : '' }}.
                </h2>

                @if ($result['failed'])
                    <div class="mt-3 overflow-x-auto rounded-md border border-amber-200 bg-white">
                        <table class="min-w-full divide-y divide-amber-100 text-sm">
                            <thead class="bg-amber-50 text-left text-xs font-semibold tracking-wide text-amber-800 uppercase">
                                <tr>
                                    <th class="px-3 py-2">Row</th>
                                    <th class="px-3 py-2">Problem</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-50">
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
                @elseif ($result['imported'] > 0)
                    <a href="{{ route('products.index') }}" class="mt-2 inline-block text-sm font-medium text-green-800 underline">View products</a>
                @endif
            </section>
        @endif

        <form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data"
              class="space-y-5 rounded-lg border border-slate-200 bg-white p-6">
            @csrf

            <div>
                <label for="file" class="mb-1 block text-sm font-medium">File <span class="text-red-500">*</span></label>
                <input id="file" name="file" type="file" required accept=".xlsx,.csv"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200">
                <p class="mt-1 text-xs text-slate-500">.xlsx or .csv, up to 5 MB.</p>
                @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-md bg-slate-50 p-4 text-sm text-slate-600">
                <p class="mb-2 font-medium text-slate-800">Expected columns (first row = headings)</p>
                <ul class="list-inside list-disc space-y-1">
                    <li><strong>Name</strong>: required</li>
                    <li><strong>Category</strong>: the name of an existing category (not case-sensitive)</li>
                    <li><strong>Description</strong>: optional</li>
                    <li><strong>Price</strong>: e.g. 49.90, up to 2 decimals</li>
                    <li><strong>Stock</strong>: a whole number, 0 or more</li>
                    <li><strong>Status</strong>: enabled or disabled (blank means enabled)</li>
                </ul>
                <p class="mt-2">Files downloaded with "Export to Excel" use the same columns, so they can be edited and imported again (as new products).</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('products.import.template') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Download template</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Import</button>
            </div>
        </form>
    </div>
</x-layout>
