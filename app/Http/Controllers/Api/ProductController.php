<?php

namespace App\Http\Controllers\Api;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteProductRequest;
use App\Http\Requests\ImportProductRequest;
use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    #[OA\Get(
        path: '/api/products',
        summary: 'List products (paginated, filterable)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['enabled', 'disabled'])),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Search by product name', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'low_stock', in: 'query', required: false, description: 'Only products with stock of 10 or less', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'sort', in: 'query', required: false, description: 'Sort field. Prefix with - for descending, e.g. -price', schema: new OA\Schema(type: 'string', enum: ['name', '-name', 'price', '-price', 'stock', '-stock', 'created_at', '-created_at'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100, minimum: 1)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated product list', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                new OA\Property(property: 'links', type: 'object'),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        return ProductResource::collection($this->products->paginate($request->filters()));
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Create a product. Send JSON, or multipart/form-data to attach an image',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(required: true, content: [
            new OA\JsonContent(ref: '#/components/schemas/ProductInput'),
            new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/ProductMultipartInput')),
        ]),
        responses: [
            new OA\Response(response: 201, description: 'Product created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->validated());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Get a single product',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function show(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        return new ProductResource($product->load('category:id,name'));
    }

    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Update a product (all fields optional). To upload an image, send POST with _method=PUT as multipart/form-data',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: [
            new OA\JsonContent(ref: '#/components/schemas/ProductInput'),
            new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/ProductMultipartInput')),
        ]),
        responses: [
            new OA\Response(response: 200, description: 'Product updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return new ProductResource($this->products->update($product, $request->validated()));
    }

    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Soft delete a product',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Product deleted.'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->products->delete($product);

        return response()->json(['message' => 'Product deleted.']);
    }

    #[OA\Delete(
        path: '/api/products/bulk',
        summary: 'Soft delete many products at once',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['ids'],
            properties: [new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Products deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: '3 product(s) deleted.'),
                new OA\Property(property: 'deleted', type: 'integer', example: 3),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function bulkDestroy(BulkDeleteProductRequest $request): JsonResponse
    {
        $deleted = $this->products->bulkDelete($request->validated('ids'));

        return response()->json([
            'message' => "{$deleted} product(s) deleted.",
            'deleted' => $deleted,
        ]);
    }

    #[OA\Get(
        path: '/api/products/export',
        summary: 'Export products to Excel (.xlsx), using the same filters as the list',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['enabled', 'disabled'])),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'low_stock', in: 'query', required: false, description: 'Only products with stock of 10 or less', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'sort', in: 'query', required: false, description: 'Sort field. Prefix with - for descending, e.g. -price', schema: new OA\Schema(type: 'string', enum: ['name', '-name', 'price', '-price', 'stock', '-stock', 'created_at', '-created_at'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Excel file', content: new OA\MediaType(
                mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                schema: new OA\Schema(type: 'string', format: 'binary'),
            )),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
        ],
    )]
    public function export(IndexProductRequest $request): BinaryFileResponse
    {
        $this->authorize('export', Product::class);

        $fileName = 'products-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new ProductsExport($this->products->query($request->filters())), $fileName);
    }

    #[OA\Post(
        path: '/api/products/import',
        summary: 'Import products from an Excel (.xlsx) or CSV file',
        description: 'Columns: Name, Category (an existing category name), Description, Price, Stock, Status (enabled/disabled, optional). Valid rows are imported; invalid rows are skipped and listed in "failed" with their row number.',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(required: ['file'], properties: [
                new OA\Property(property: 'file', type: 'string', format: 'binary', description: '.xlsx or .csv, max 5 MB'),
            ]),
        )),
        responses: [
            new OA\Response(response: 200, description: 'Import finished', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: '18 product(s) imported, 2 row(s) skipped.'),
                new OA\Property(property: 'imported', type: 'integer', example: 18),
                new OA\Property(property: 'failed', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'row', type: 'integer', example: 7),
                    new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'), example: ['The price field is required.']),
                ])),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function import(ImportProductRequest $request): JsonResponse
    {
        $result = $this->products->import($request->file('file'));

        return response()->json([
            'message' => "{$result['imported']} product(s) imported, ".count($result['failed']).' row(s) skipped.',
            ...$result,
        ]);
    }

    #[OA\Get(
        path: '/api/products/{id}/stock-movements',
        summary: "A product's stock history, newest first",
        security: [['sanctum' => []]],
        tags: ['Stock history'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated stock movements (20 per page)', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StockMovement')),
                new OA\Property(property: 'links', type: 'object'),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function stockMovements(Product $product): AnonymousResourceCollection
    {
        $this->authorize('view', $product);

        return StockMovementResource::collection($this->products->stockHistory($product));
    }

    #[OA\Get(
        path: '/api/products/trash',
        summary: 'List products in the trash (soft deleted), most recently deleted first',
        security: [['sanctum' => []]],
        tags: ['Trash'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Search by product name', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100, minimum: 1)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of deleted products (each includes deleted_at)', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                new OA\Property(property: 'links', type: 'object'),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
        ],
    )]
    public function trash(IndexProductRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewTrash', Product::class);

        return ProductResource::collection($this->products->trashed($request->filters()));
    }

    #[OA\Patch(
        path: '/api/products/{id}/restore',
        summary: 'Restore a product from the trash',
        security: [['sanctum' => []]],
        tags: ['Trash'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product restored', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/Conflict', response: 409, description: 'The product is not in the trash'),
        ],
    )]
    public function restore(Product $product): ProductResource
    {
        $this->authorize('restore', $product);

        return new ProductResource($this->products->restore($product));
    }

    #[OA\Delete(
        path: '/api/products/{id}/force',
        summary: 'Permanently delete a product. It must be in the trash first',
        security: [['sanctum' => []]],
        tags: ['Trash'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product permanently deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Product permanently deleted.'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/Conflict', response: 409, description: 'The product is not in the trash'),
        ],
    )]
    public function forceDestroy(Product $product): JsonResponse
    {
        $this->authorize('forceDelete', $product);

        $this->products->forceDelete($product);

        return response()->json(['message' => 'Product permanently deleted.']);
    }
}
