<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\NoCache;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Routes
    |--------------------------------------------------------------------------
    |
    | The web middleware provides the browser session and CSRF support.
    | The auth middleware ensures that private channels receive an
    | authenticated Laravel user before authorization is attempted.
    |
    */
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        [
            'middleware' => [
                'web',
                'auth',
            ],
        ],
    )

    ->withMiddleware(function (Middleware $middleware) {

        /*
        |--------------------------------------------------------------------------
        | Middleware Aliases
        |--------------------------------------------------------------------------
        |
        | These aliases allow routes to reference your custom middleware
        | using short names.
        |
        */
        $middleware->alias([

            /*
            |--------------------------------------------------------------------------
            | Authentication
            |--------------------------------------------------------------------------
            */
            'auth' => Authenticate::class,

            /*
            |--------------------------------------------------------------------------
            | Cache Protection
            |--------------------------------------------------------------------------
            */
            'no.cache' => NoCache::class,

            /*
            |--------------------------------------------------------------------------
            | Admin Authorization
            |--------------------------------------------------------------------------
            */
            'admin.only' => \App\Http\Middleware\AdminOnly::class,

            /*
            |--------------------------------------------------------------------------
            | Superadmin Authorization
            |--------------------------------------------------------------------------
            */
            'superadmin.only' => \App\Http\Middleware\SuperAdminOnly::class,
        ]);

       
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();