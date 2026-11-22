<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API tokens carry abilities: "read" for GET requests, "write" for anything
 * that changes data. A read-only token can never change data, whatever the
 * user's role. (The role still applies on top: a write token can't do more
 * than its owner is allowed to.)
 *
 * Tokens from POST /api/login have every ability ("*"). Requests made with
 * a browser session aren't limited by this middleware.
 */
class EnsureTokenCanAct
{
    public const READ = 'read';

    public const WRITE = 'write';

    public function handle(Request $request, Closure $next): Response
    {
        $needed = $request->isMethodSafe() ? self::READ : self::WRITE;

        if ($request->user() && ! $request->user()->tokenCan($needed)) {
            abort(Response::HTTP_FORBIDDEN, "This API token is not allowed to {$needed}.");
        }

        return $next($request);
    }
}
