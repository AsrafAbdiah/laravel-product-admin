<?php

namespace App\Http\Controllers\Web;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteProductRequest;
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
        return view('products.index', [
            'products' => $this->products->paginate($request->filters()),
            'categories' => $this->categories(),
            'filters' => $request->filters(),
        ]);
    }

    public function create(): View
    {
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
        return view('products.show', [
            'product' => $product->load('category:id,name'),
        ]);
    }

    public function edit(Product $product): View
    {
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
        $this->products->delete($product);

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function bulkDestroy(BulkDeleteProductRequest $request): RedirectResponse
    {
        $deleted = $this->products->bulkDelete($request->validated('ids'));

        return back()->with('success', "{$deleted} product(s) deleted.");
    }

    public function export(IndexProductRequest $request): BinaryFileResponse
    {
        $fileName = 'products-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new ProductsExport($this->products->query($request->filters())), $fileName);
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
