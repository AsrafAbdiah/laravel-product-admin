<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: '/api/categories',
        summary: 'List all categories with their product counts',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        responses: [
            new OA\Response(response: 200, description: 'Category list', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        // Unpaginated on purpose: the list is small and feeds dropdowns.
        return CategoryResource::collection(
            Category::select(['id', 'name'])->withCount('products')->orderBy('name')->get()
        );
    }

    #[OA\Post(
        path: '/api/categories',
        summary: 'Create a category',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')),
        responses: [
            new OA\Response(response: 201, description: 'Category created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated())->loadCount('products');

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/categories/{id}',
        summary: 'Get a single category',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Category detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('products'));
    }

    #[OA\Put(
        path: '/api/categories/{id}',
        summary: 'Rename a category',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')),
        responses: [
            new OA\Response(response: 200, description: 'Category updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category->loadCount('products'));
    }

    #[OA\Delete(
        path: '/api/categories/{id}',
        summary: 'Delete a category (only when it has no products, including ones in the trash)',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Category deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Category deleted.'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/Conflict', response: 409),
        ],
    )]
    public function destroy(Category $category): JsonResponse
    {
        if ($category->hasProducts()) {
            return response()->json([
                'message' => 'This category still has products. Move or permanently delete them first.',
            ], 409);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
