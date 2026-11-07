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
        // School-provisioned accounts (notes 12) must replace their temporary
        // password before reaching anything but the change-password screen.
        // This ran as the last entry of the "web" group on Laravel 8; appending
        // it keeps that order, so it also runs after the session is started.
        $middleware->web(append: [
            \App\Http\Middleware\EnsurePasswordIsChanged::class,
        ]);

        // The only route middleware this app defines itself (routes/web.php
        // uses role:admin,ceo / role:tutor / role:parent / role:learner).
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
