<?php

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
    ->withMiddleware(function (Middleware $middleware) {
        
        // Bezpieczna obsługa niezalogowanych użytkowników
        $middleware->redirectGuestsTo(function (Request $request) {
            // 1. Jeśli to zapytanie do API lub żąda JSON, zwróć czysty błąd 401
            if ($request->is('api/*') || $request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }
            
            // 2. W przeciwnym wypadku przekieruj na stronę główną (zamiast crashować na route('login'))
            return '/'; 
        });

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
