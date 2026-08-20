<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SkipNgrokWarning
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // إضافة الترويسة لتجاوز صفحة التحذير البيضاء
        $response->headers->set('ngrok-skip-browser-warning', 'true');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, ngrok-skip-browser-warning');

        return $response;
    }
}


