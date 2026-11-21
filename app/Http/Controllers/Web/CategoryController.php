<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::withCount('products')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('categories.create', ['category' => new Category]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->hasProducts()) {
            return back()->with('error', "\"{$category->name}\" still has products. Move or permanently delete them first.");
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
