<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ApiLog extends Model
{
    use HasFactory;

    public $table = 'api_logs';

    protected $fillable = [
        'method',
        'url',
        'headers',
        'request_body',
        'response_body',
        'status_code',
        'ip_address',
        'driver_id'
    ];

    protected $casts = [
        'headers' => 'array',
        'request_body' => 'array',
        'response_body' => 'array',
    ];

    public static function createLog(Request $request, $driverId = null)
    {
        return self::create([
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'headers'      => $request->headers->all(), // $casts will auto-convert this to JSON
            'request_body' => $request->all(),          // $casts will auto-convert this to JSON
            'ip_address'   => $request->ip(),
            'driver_id'    => $driverId,
            // response_body and status_code are intentionally left blank until it finishes
        ]);
    }

    /**
     * Updates the existing log instance with the final response data.
     */
    public function updateResponse($responseBody, $statusCode)
    {
        $this->update([
            'response_body' => $responseBody, // $casts will auto-convert this to JSON
            'status_code'   => $statusCode,
        ]);
    }
}
