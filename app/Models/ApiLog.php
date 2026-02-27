<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    
}
