<?php

namespace App\Http\Controllers\Api;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteProductRequest;
use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
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
        return ProductResource::collection($this->products->paginate($request->filters()));
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Create a product',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')),
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
        return new ProductResource($product->load('category:id,name'));
    }

    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Update a product (all fields optional)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')),
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
        $fileName = 'products-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new ProductsExport($this->products->query($request->filters())), $fileName);
    }
}
