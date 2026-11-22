<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Product Admin API',
    description: 'RESTful API for managing products and categories. All endpoints except login require an admin Bearer token: call POST /api/login, then click "Authorize" and paste the token.',
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'Local server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token',
    description: 'Paste the token returned by POST /api/login.',
)]
#[OA\Tag(name: 'Auth', description: 'Admin login and logout')]
#[OA\Tag(name: 'Dashboard', description: 'Summary numbers')]
#[OA\Tag(name: 'Products', description: 'Product management')]
#[OA\Tag(name: 'Categories', description: 'Product categories')]
#[OA\Tag(name: 'Trash', description: 'Restore or permanently delete soft-deleted products')]
#[OA\Tag(name: 'Stock history', description: 'Every change to a product\'s stock, with who made it')]
abstract class Controller
{
    use AuthorizesRequests;
}
