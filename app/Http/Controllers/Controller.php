<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'BandarOS API',
    description: 'REST API for BandarOS, inventory for small shops. Every endpoint except login needs a Bearer token: call POST /api/login (or create one under Account settings > API tokens), then click "Authorize" and paste it. Requests work on your current shop. What you may do depends on your role there (owner, admin, staff, viewer) and on the token: read-only tokens get 403 on anything that changes data.',
)]
#[OA\Server(url: '/', description: 'This server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token',
    description: 'Paste the token returned by POST /api/login.',
)]
#[OA\Tag(name: 'Auth', description: 'Login and logout')]
#[OA\Tag(name: 'Dashboard', description: 'Summary numbers')]
#[OA\Tag(name: 'Products', description: 'Product management')]
#[OA\Tag(name: 'Categories', description: 'Product categories')]
#[OA\Tag(name: 'Trash', description: 'Restore or permanently delete soft-deleted products')]
#[OA\Tag(name: 'Stock history', description: 'Every change to a product\'s stock, with who made it')]
abstract class Controller
{
    use AuthorizesRequests;
}
