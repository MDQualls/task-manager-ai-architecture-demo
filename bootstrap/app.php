<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // statefulApi() is intentionally omitted.
        // This project uses Bearer token auth (Authorization: Bearer <token>),
        // which is stateless and does not require session-based CSRF protection.
        // Sanctum's auth:sanctum guard verifies Bearer tokens without needing
        // the EnsureFrontendRequestsAreStateful middleware.
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // ── JSON rendering ─────────────────────────────────────────────────
        // Force JSON responses for all /api/* routes and any request that
        // explicitly expects JSON (Accept: application/json).
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        // ── Noise suppression ──────────────────────────────────────────────
        // These are expected client-side outcomes, not application errors.
        // Excluding them keeps the error log meaningful and signal-rich.
        $exceptions->dontReport([
            ValidationException::class,
            AuthenticationException::class,
        ]);

        // ── Global render fallback for API routes ──────────────────────────
        // The TaskController catches Throwable on every action method, so this
        // handler is only reached by exceptions that originate outside a
        // controller body — e.g. middleware failures, unresolvable bindings,
        // or truly unexpected routing errors.
        //
        // Returning null defers to Laravel's default renderer (which already
        // produces JSON for HTTP exceptions when shouldRenderJsonWhen is true).
        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {

            // Only intercept API requests
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            // Let Laravel handle unauthenticated responses (returns 401 JSON)
            if ($e instanceof AuthenticationException) {
                return null;
            }

            // HTTP exceptions (404, 405, 401, 403, …) already carry the correct
            // status code and message — let Laravel's default formatter handle them.
            if ($e instanceof HttpExceptionInterface) {
                return null;
            }

            // ValidationException (422) is handled by Laravel's built-in renderer.
            if ($e instanceof ValidationException) {
                return null;
            }

            // Everything else that reaches here is an unhandled infrastructure or
            // programming error. Log it once (controller already logged its own
            // caught errors, so this fires only for middleware / boot-level failures)
            // and return a safe, generic 500 to the client.
            Log::error('Unhandled exception reached global API handler', [
                'exception'  => get_class($e),
                'error'      => $e->getMessage(),
                'caused_by'  => $e->getPrevious()?->getMessage(),
                'http_method'=> $request->method(),
                'url'        => $request->fullUrl(),
            ]);

            return response()->json(
                ['message' => 'An unexpected error occurred. Please try again later.'],
                500,
            );
        });
    })->create();
