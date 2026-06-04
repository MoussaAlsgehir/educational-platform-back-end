<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\GuestApiMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->alias([
            'role' => CheckRole::class,
            'guest_api' => GuestApiMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
