<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResource;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GuestApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (Auth::guard('sanctum')->check()) {
            return ApiResource::sendResponse("You are already logged in.", null, 400);
        }
        return $next($request);
    }
}
