<?php

namespace App\Http\Controllers;

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
#[OA\Tag(name: 'Products', description: 'Product management')]
#[OA\Tag(name: 'Categories', description: 'Product categories')]
abstract class Controller
{
    //
}
