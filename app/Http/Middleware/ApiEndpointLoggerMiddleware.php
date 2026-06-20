<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiEndpointLoggerMiddleware
{
    private $startTime;

    public function handle(Request $request, Closure $next): Response
    {
        $this->startTime = microtime(true);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $duration = $this->startTime ? round((microtime(true) - $this->startTime) * 1000, 2) : 0;

        $logData = [
            'ip'          => $request->ip(),
            'method'      => $request->method(),
            'endpoint'    => $request->fullUrl(),
            'user_id'     => $request->user() ? $request->user()->id : 'Guest',
            'status'      => $response->getStatusCode(),
            'duration_ms' => $duration,
            'payload'     => $request->except(['password', 'password_confirmation', 'cvv', 'card_number']),
        ];

        Log::channel('api_endpoints')->info("Endpoint: [{$request->method()}] -> {$request->path()} | Status: {$response->getStatusCode()} | Time: {$duration}ms", $logData);
    }
}
