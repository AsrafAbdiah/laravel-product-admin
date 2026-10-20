<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Only let administrators through. Runs after the auth middleware,
     * so an unauthenticated request never reaches this point.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'Only administrators can access this resource.');
        }

        return $next($request);
    }
}
