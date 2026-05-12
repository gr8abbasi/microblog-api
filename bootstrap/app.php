<?php

use App\GraphQL\Exceptions\GraphQLExceptionHandler;
use GraphQL\Error\ClientAware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Intercept all Lighthouse ClientAware exceptions before they reach
        // Lighthouse's v6.66 pipeline bug + Laravel 11
        $exceptions->renderable(
            fn (ClientAware $e, Request $request) =>
                app(GraphQLExceptionHandler::class)->handle($e, $request)
        );
    })->create();