<?php

use App\Helpers\ResponseHelper;
use App\Http\Middleware\ApiEndpointLoggerMiddleware;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->api(append: [
            ApiEndpointLoggerMiddleware::class,
        ]);

        $middleware->throttleWithRedis();

        $middleware->alias([
            'role' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $requestContext = function (Request $request): array {
            return [
                'endpoint' => $request->fullUrl(),
                'method'   => $request->method(),
                'user_id'  => $request->user()?->id ?? 'Guest',
                'ip'       => $request->ip(),
            ];
        };

        //  warnings.log
        $exceptions->report(function (ValidationException $e) use ($requestContext) {
            Log::channel('warning_logs')->warning('Validation failed.', [
                ...$requestContext(request()),
                'errors' => $e->errors(),
            ]);
        });

        $exceptions->report(function (AuthenticationException $e) use ($requestContext) {
            Log::channel('warning_logs')->warning('Unauthenticated request.', [
                ...$requestContext(request()),
                'message' => $e->getMessage(),
            ]);
        });

        //  errors.log
        $exceptions->report(function (Throwable $e) use ($requestContext) {
            Log::channel('error_logs')->error('Unhandled exception occurred.', [
                ...$requestContext(request()),
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => collect($e->getTrace())->take(5)->toArray(),
            ]);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ResponseHelper::jsonResponse(null, 'Unauthenticated.', 401, false);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ResponseHelper::jsonResponse(null, 'Resource not found.', 404, false);
            }
        });

    })->create();
