<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // 🔥 INI BAGIAN PENTING (REGISTER MIDDLEWARE ALIAS)
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'webrole' => \App\Http\Middleware\WebRole::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'login',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, \Illuminate\Http\Request $request) {
            return redirect('/login')->with('error', 'Sesi login kedaluwarsa. Silakan refresh halaman lalu login kembali.');
        });
    })->create();
