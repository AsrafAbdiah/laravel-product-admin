<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Quick search for the Ctrl+K command palette. Both models use the
 * BelongsToTeam global scope, so only the current shop's records can match.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $term = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));

        if ($term === '') {
            return response()->json(['products' => [], 'categories' => []]);
        }

        $products = $this->whereNameContains(Product::query(), $term)
            ->with('category:id,name')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'category_id', 'stock'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name,
                'stock' => $product->stock,
                'url' => route('products.show', $product),
            ]);

        $categories = $this->whereNameContains(Category::query(), $term)
            ->withCount('products')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'products_count' => $category->products_count,
                'url' => route('products.index', ['category_id' => $category->id]),
            ]);

        return response()->json(['products' => $products, 'categories' => $categories]);
    }

    /**
     * "name contains the term", treating % and _ typed by the user as plain
     * characters rather than LIKE wildcards. The escape character is given
     * explicitly because MySQL and SQLite (used by the tests) differ by default.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function whereNameContains(Builder $query, string $term): Builder
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        return $query->whereRaw($query->qualifyColumn('name').' like ? escape ?', ['%'.$escaped.'%', '!']);
    }
}
