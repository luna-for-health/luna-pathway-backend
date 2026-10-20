<?php

namespace App\Http\Middleware;

use App\Models\Secretary;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to requests authenticated as a Secretary (staff), rejecting a
 * valid Sanctum token that belongs to a patient User instead.
 */
class EnsureSecretary
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Secretary) {
            abort(403, 'This action requires a staff account.');
        }

        return $next($request);
    }
}
