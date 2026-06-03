<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\GuestApiMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Helpers\ApiResource;
use Illuminate\Auth\AuthenticationException;
use Mockery\Matcher\Not;
use Nette\Schema\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
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
        $exceptions->render(function (NotFoundHttpException $exception) {
            return ApiResource::sendResponse($exception->getMessage(), null, 404);
        });

        $exceptions->render(function(ValidationException $exception){
            return ApiResource::sendResponse($exception->getMessage(), null, 422);
        });

        $exceptions->render(function(AuthenticationException $exception){
            return ApiResource::sendResponse($exception->getMessage(), null, 401);
        });

        $exceptions->render(function(MethodNotAllowedHttpException $exception){
            return ApiResource::sendResponse($exception->getMessage(), null, 405);
        });


        $exceptions->render(function(Throwable $exception){
            return ApiResource::sendResponse($exception->getMessage(), null, 500);
        });


    })->create();
