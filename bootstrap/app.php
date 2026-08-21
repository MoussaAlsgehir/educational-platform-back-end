<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\GuestApiMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Helpers\ApiResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => CheckRole::class,
            'guest_api' => GuestApiMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {


        $exceptions->render(function (AuthenticationException $exception, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResource::sendResponse('Unauthenticated.', null, 401);
            }
        });

        // 2. أخطاء البيانات (Validation)
        $exceptions->render(function (ValidationException $exception, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResource::sendResponse($exception->getMessage(), $exception->errors(), 422);
            }
        });

        // 3. الراوت غير موجود (404)
        $exceptions->render(function (NotFoundHttpException $exception, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResource::sendResponse('Resource not found.', null, 404);
            }
        });

        // 4. الميثود غلط (مثلاً بعت POST ع راوت GET)
        $exceptions->render(function (MethodNotAllowedHttpException $exception, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResource::sendResponse($exception->getMessage(), null, 405);
            }
        });

        // 5. لأي خطأ تاني (500 Server Error)
        $exceptions->render(function (Throwable $exception, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $message = app()->isProduction() ? 'Server Error' : $exception->getMessage();
                return ApiResource::sendResponse($message, null, 500);
            }
        });

    })->create();
