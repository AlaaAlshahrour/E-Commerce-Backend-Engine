<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiEndpointLoggerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $logData = [
            'ip'         => $request->ip(),
            'method'     => $request->method(),
            'endpoint'   => $request->fullUrl(),
            'user_id'    => $request->user() ? $request->user()->id : 'Guest',
            'status'     => $response->getStatusCode(),
            'duration_ms'=> $duration,
            'payload'    => $request->except(['password', 'password_confirmation']),
        ];

        Log::channel('api_endpoints')->info("Endpoint Accessed: [{$request->method()}] -> {$request->path()}", $logData);

        return $response;
    }
}
