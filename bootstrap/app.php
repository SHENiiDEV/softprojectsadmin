<?php

use App\Http\Middleware\CheckFeatureEnabled;
use App\Http\Middleware\SetUserPreferences;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
            'api/v1/webhooks/gmail-alert',
        ]);

        $middleware->alias([
            'feature' => CheckFeatureEnabled::class,
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            SetUserPreferences::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
