<?php

namespace App\Http\Controllers\Web;

use App\Exports\ProductsExport;
use App\Exports\ProductsTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteProductRequest;
use App\Http\Requests\ImportProductRequest;
use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(IndexProductRequest $request): View
    {
        $this->authorize('viewAny', Product::class);

        return view('products.index', [
            'products' => $this->products->paginate($request->filters()),
            'categories' => $this->categories(),
            'filters' => $request->filters(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', [
            'product' => new Product,
            'categories' => $this->categories(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        return redirect()->route('products.show', $product)->with('success', 'Product created.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        return view('products.show', [
            'product' => $product->load('category:id,name'),
            'movements' => $this->products->stockHistory($product),
        ]);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', [
            'product' => $product,
            'categories' => $this->categories(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->products->delete($product);

        return redirect()->route('products.index')->with('success', 'Product moved to the trash.');
    }

    public function bulkDestroy(BulkDeleteProductRequest $request): RedirectResponse
    {
        $deleted = $this->products->bulkDelete($request->validated('ids'));

        return back()->with('success', "{$deleted} product(s) moved to the trash.");
    }

    public function export(IndexProductRequest $request): BinaryFileResponse
    {
        $this->authorize('export', Product::class);

        $fileName = 'products-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new ProductsExport($this->products->query($request->filters())), $fileName);
    }

    public function importForm(): View
    {
        $this->authorize('import', Product::class);

        return view('products.import', [
            'result' => session('import_result'),
        ]);
    }

    public function import(ImportProductRequest $request): RedirectResponse
    {
        $result = $this->products->import($request->file('file'));

        return redirect()->route('products.import')->with('import_result', $result);
    }

    public function importTemplate(): BinaryFileResponse
    {
        $this->authorize('import', Product::class);

        return Excel::download(new ProductsTemplateExport, 'product-import-template.xlsx');
    }

    public function trash(IndexProductRequest $request): View
    {
        $this->authorize('viewTrash', Product::class);

        return view('products.trash', [
            'products' => $this->products->trashed($request->filters()),
            'filters' => $request->filters(),
        ]);
    }

    public function restore(Product $product): RedirectResponse
    {
        $this->authorize('restore', $product);

        $this->products->restore($product);

        return back()->with('success', "\"{$product->name}\" restored.");
    }

    public function forceDestroy(Product $product): RedirectResponse
    {
        $this->authorize('forceDelete', $product);

        $name = $product->name;

        $this->products->forceDelete($product);

        return back()->with('success', "\"{$name}\" permanently deleted.");
    }

    /**
     * Categories for the filter and form dropdowns.
     *
     * @return Collection<int, Category>
     */
    private function categories(): Collection
    {
        return Category::orderBy('name')->get(['id', 'name']);
    }
}
