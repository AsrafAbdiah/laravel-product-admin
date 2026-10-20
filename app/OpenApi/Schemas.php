<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Reusable OpenAPI schemas referenced by the API controllers.
 * This class holds documentation only and is never instantiated.
 */
#[OA\Schema(
    schema: 'Category',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
    ],
)]
#[OA\Schema(
    schema: 'Product',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Wireless Mouse'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Ergonomic 2.4GHz mouse'),
        new OA\Property(property: 'price', type: 'string', example: '49.90', description: 'Decimal as string to avoid float rounding'),
        new OA\Property(property: 'stock', type: 'integer', example: 120),
        new OA\Property(property: 'enabled', type: 'boolean', example: true),
        new OA\Property(property: 'category_id', type: 'integer', example: 1),
        new OA\Property(property: 'category', ref: '#/components/schemas/Category'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'ProductInput',
    required: ['category_id', 'name', 'price', 'stock'],
    properties: [
        new OA\Property(property: 'category_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Wireless Mouse'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Ergonomic 2.4GHz mouse'),
        new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0, example: 49.90),
        new OA\Property(property: 'stock', type: 'integer', minimum: 0, example: 120),
        new OA\Property(property: 'enabled', type: 'boolean', example: true),
    ],
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 4),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 50),
    ],
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The name field is required.'),
        new OA\Property(property: 'errors', type: 'object', example: ['name' => ['The name field is required.']]),
    ],
)]
#[OA\Response(
    response: 'Unauthenticated',
    description: 'Missing or invalid token',
    content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]),
)]
#[OA\Response(
    response: 'Forbidden',
    description: 'Authenticated user is not an admin',
    content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Only administrators can access this resource.')]),
)]
#[OA\Response(
    response: 'ValidationFailed',
    description: 'Validation failed',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
)]
#[OA\Response(
    response: 'NotFound',
    description: 'Resource not found',
    content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Resource not found.')]),
)]
final class Schemas
{
}
