<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiEndpointLoggerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('log_start_time', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $start    = $request->attributes->get('log_start_time');
        $duration = $start ? round((microtime(true) - $start) * 1000, 2) : 0;

        $logData = [
            'ip'          => $request->ip(),
            'method'      => $request->method(),
            'endpoint'    => $request->fullUrl(),
            'user_id'     => $request->user()?->id ?? 'Guest',
            'status'      => $response->getStatusCode(),
            'duration_ms' => $duration,
            'payload'     => $request->except(['password', 'password_confirmation', 'cvv', 'card_number']),
        ];

        $channel = $this->resolveChannel($request->path());

        Log::channel($channel)->info(
            "Endpoint: [{$request->method()}] -> {$request->path()} | Status: {$response->getStatusCode()} | Time: {$duration}ms",
            $logData
        );
    }

    private function resolveChannel(string $path): string
    {
        return match (true) {
            str_starts_with($path, 'api/login'),
            str_starts_with($path, 'api/register'),
            str_starts_with($path, 'api/logout'),
            str_starts_with($path, 'api/me')          => 'auth_logs',

            str_starts_with($path, 'api/cart')        => 'cart_logs',

            str_starts_with($path, 'api/products'),
            str_starts_with($path, 'api/categories')  => 'products_logs',

            str_starts_with($path, 'api/orders')      => 'orders_logs',

            str_starts_with($path, 'api/inventory')   => 'inventory_logs',

            str_starts_with($path, 'api/wallet')      => 'wallet_logs',

            str_starts_with($path, 'api/nodes'),
            str_starts_with($path, 'api/node')        => 'nodes_logs',

            str_starts_with($path, 'api/daily-sales-report'),
            str_starts_with($path, 'api/reports')     => 'reports_logs',

            default                                   => 'others_logs',
        };
    }
}
