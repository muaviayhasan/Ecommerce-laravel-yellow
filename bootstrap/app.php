<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Unauthenticated admin visitors get the staff login; everyone else the storefront login.
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin', 'admin/*') ? route('admin.login') : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Persist unhandled exceptions to the database (Admin → Error Logs) on top
        // of the normal log channel. ErrorLogger swallows its own failures, so this
        // can never break the request that raised the exception.
        $exceptions->report(function (\Throwable $e) {
            app(\App\Services\ErrorLogger::class)->log($e);
        });

        // A stale CSRF token (e.g. a form left open past the session lifetime)
        // shouldn't dump the bare 419 page. Bounce back to the form with the
        // input preserved and a notice so nothing typed is lost.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session was refreshed. Please retry.'], 419);
            }

            return back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', 'Your session timed out and has been refreshed — please review and submit again.');
        });
    })->create();
