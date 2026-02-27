<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'module',
        'old_data',
        'new_data',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => $this->user_name ?? 'Admin'
        ]);
    }

    /**
     * Scope for specific module
     */
    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for specific action
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted old data for display
     */
    public function getFormattedOldDataAttribute()
    {
        return $this->formatDataForDisplay($this->old_data);
    }

    /**
     * Get formatted new data for display
     */
    public function getFormattedNewDataAttribute()
    {
        return $this->formatDataForDisplay($this->new_data);
    }

    /**
     * Format data for display (hide sensitive info)
     */
    private function formatDataForDisplay($data)
    {
        if (!$data) return null;
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'remember_token', 'api_token'];
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '********';
            }
        }
        
        return $data;
    }
}