<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: '/api/dashboard',
        summary: 'Summary numbers for the dashboard',
        security: [['sanctum' => []]],
        tags: ['Dashboard'],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard data', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'totals', type: 'object', properties: [
                    new OA\Property(property: 'products', type: 'integer', example: 50),
                    new OA\Property(property: 'enabled', type: 'integer', example: 41),
                    new OA\Property(property: 'disabled', type: 'integer', example: 9),
                    new OA\Property(property: 'low_stock', type: 'integer', example: 6, description: 'Products with stock of 10 or less'),
                    new OA\Property(property: 'in_trash', type: 'integer', example: 3),
                    new OA\Property(property: 'inventory_value', type: 'string', example: '84210.50', description: 'Sum of price x stock for active products'),
                ]),
                new OA\Property(property: 'products_per_category', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                new OA\Property(property: 'low_stock_products', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                new OA\Property(property: 'recent_stock_movements', type: 'array', items: new OA\Items(ref: '#/components/schemas/StockMovement')),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
        ],
    )]
    public function __invoke(DashboardService $dashboard): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        return response()->json([
            'totals' => $dashboard->totals(),
            'products_per_category' => CategoryResource::collection($dashboard->productsPerCategory()),
            'low_stock_products' => ProductResource::collection($dashboard->lowStockProducts()),
            'recent_stock_movements' => StockMovementResource::collection($dashboard->recentStockMovements()),
        ]);
    }
}
