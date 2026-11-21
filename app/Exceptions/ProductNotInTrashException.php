<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown when restore or permanent delete is attempted on a product
 * that is not in the trash. Laravel calls render() automatically,
 * so controllers don't need their own checks.
 */
class ProductNotInTrashException extends RuntimeException
{
    public function __construct(string $message = 'This product is not in the trash.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): Response
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], Response::HTTP_CONFLICT);
        }

        return back()->with('error', $this->getMessage());
    }
}
