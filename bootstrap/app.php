<?php

use App\Data\Middleware\EnsureAppIsUnlocked;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RejectUnknownHosts;
use App\Profiles\Middleware\EnsureSetupIsComplete;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../app/Profiles/routes.php',
            __DIR__.'/../app/Transactions/routes.php',
            __DIR__.'/../app/Tax/routes.php',
            __DIR__.'/../app/Compliance/routes.php',
            __DIR__.'/../app/Returns/routes.php',
            __DIR__.'/../app/Books/routes.php',
            __DIR__.'/../app/Data/routes.php',
            __DIR__.'/../app/Notices/routes.php',
            __DIR__.'/../app/Withholding/routes.php',
            __DIR__.'/../app/Loans/routes.php',
            __DIR__.'/../app/Payroll/routes.php',
            __DIR__.'/../app/Letters/routes.php',
            __DIR__.'/../app/Corporations/routes.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Returns/Commands',
        __DIR__.'/../app/Tax/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'setup.complete' => EnsureSetupIsComplete::class,
        ]);

        // Before anything else: requests addressed to another host get nothing.
        $middleware->prepend(RejectUnknownHosts::class);

        $middleware->web(append: [
            EnsureAppIsUnlocked::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
