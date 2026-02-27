<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiLog;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Driver;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $this->logRequest($request, $response);
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

            // Skip response_body if it exceeds the safe length (e.g., 65535 for TEXT)
            $maxLength = 65535; // Adjust based on your column type
            $storeResponseBody = $responseLength <= $maxLength ? $encodedResponseBody : null;

            if ($responseLength > $maxLength) {
                Log::warning('Skipped storing response_body due to excessive length', [
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