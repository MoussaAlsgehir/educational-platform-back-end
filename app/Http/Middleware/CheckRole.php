<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user() || !$request->user()->roles()->whereIn('name', $roles)->exists())
            return ApiResource::sendResponse("Unauthorized,You must be a " . implode(', ', $roles) . " to access this resource", null, 403);

        return $next($request);
    }
}
