<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'api*',
            'client/*',
            'client*',
            'E-Parkir/*',
            'E-Parkir*',
            'api/scan.php',
            'api/ticket',
            'api/ticket.php',
            'api/override.php',
            'api/telemetry.php',
            'api/client.php',
            'client/api-status',
            'client/api_status.php',
            'client/login',
            'api_status.php',
            'api-status',
            'admin/api-stats',
            'admin/monitoring/tap',
            'admin/monitoring/checkout',
            'admin/monitoring/override',
            'admin/monitoring/ticket',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        $middleware->api(remove: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
