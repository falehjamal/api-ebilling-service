<?php

use App\Http\Middleware\RefreshSanctumTokenExpiration;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'refresh.sanctum.token' => RefreshSanctumTokenExpiration::class,
        ]);

        $middleware->redirectGuestsTo(
            // Default Laravel memanggil route('login') — proyek ini API-only, belum ada route web login.
            // Tanpa override, browser ke /api/* memicu RouteNotFoundException (500) saat auth gagal.
            function () {
                return null;
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            function (Request $request, Throwable $e) {
                if ($request->is('api/*')) {
                    return true;
                }

                return $request->expectsJson();
            }
        );
    })->create();
