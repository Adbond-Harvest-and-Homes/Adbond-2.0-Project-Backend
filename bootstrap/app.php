<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

use app\Http\Middleware\ClientAuth;
use app\Http\Middleware\UserAuth;
use app\Http\Middleware\HRAuth;
use app\Http\Middleware\SuperAdminAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function ($router) {
            Route::prefix('api')
                ->name('api.v2.')
                ->namespace('app\Http\Controllers')
                ->group(base_path('routes/api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'clientAuth' => ClientAuth::class,
            'userAuth' => UserAuth::class,
            'hrAuth' => HRAuth::class,
            'superAdminAuth' => SuperAdminAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
