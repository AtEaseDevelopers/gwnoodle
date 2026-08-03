<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiLog;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Driver;
use App\Models\ActivityLog;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $this->logRequest($request, $response);

        // Guaranteed reset of ActivityLog::actingAs() regardless of what the
        // controller did (early return, exception, success) - so a driver
        // actor set in this request can never leak into the next request
        // handled by the same worker.
        ActivityLog::clearActingAs();

        return $response;
    }

    protected function logRequest(Request $request, Response $response)
    {
        try {
            $driver = Driver::where('session', $request->header('session'))->first();

            Log::debug('Attempting to log API request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
                'request_body' => $request->all(),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'driver_id' => $driver ? $driver->id : null,
                'timestamp' => now()->toDateTimeString()
            ]);

            $headers = $request->headers->all();
            $requestBody = $request->all();
            $responseBody = $this->getResponseContent($response);
            $encodedResponseBody = json_encode($responseBody, JSON_INVALID_UTF8_IGNORE);
            $responseLength = strlen($encodedResponseBody);

            // response_body is a TEXT column (65535 byte limit). Rather than
            // discarding the whole thing when it's too long (e.g. a full
            // debug-mode stack trace on a 500), keep a truncated prefix -
            // that still preserves the exception message and the start of
            // the trace, which is what's actually useful for debugging.
            $maxLength = 65535;
            $storeResponseBody = $responseLength <= $maxLength
                ? $encodedResponseBody
                : substr($encodedResponseBody, 0, $maxLength - 20) . '...[TRUNCATED]';

            if ($responseLength > $maxLength) {
                Log::warning('Truncated response_body due to excessive length', [
                    'url' => $request->fullUrl(),
                    'response_length' => $responseLength,
                    'max_length' => $maxLength,
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            $log = ApiLog::create([
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'headers' => json_encode($headers),
                'request_body' => json_encode($requestBody),
                'response_body' => $storeResponseBody,
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'driver_id' => $driver ? $driver->id : null,
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log API request', [
                'message' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toDateTimeString()
            ]);
        }
    }

    protected function getResponseContent(Response $response)
    {
        return json_decode($response->getContent(), true) ?? $response->getContent();
    }
}