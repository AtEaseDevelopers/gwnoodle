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
        'description',
        'table_name',
        'record_id',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
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

    /**
     * Turn a raw old_data/new_data payload into a friendlier associative
     * array for display: drops the internal id, resolves product_id into
     * a product name, and turns status into an Active/Inactive label.
     */
    public static function formatDataForCompare($data): array
    {
        if (empty($data)) {
            return [];
        }

        $data = (array) $data;
        unset($data['id']);

        if (array_key_exists('product_id', $data)) {
            $productId = $data['product_id'];
            unset($data['product_id']);
            $product = $productId ? \App\Models\Product::find($productId) : null;
            $data['product_name'] = $product->name ?? 'Unknown';
        }

        if (array_key_exists('status', $data)) {
            $data['status'] = ((int) $data['status'] === 1) ? 'Active' : 'Inactive';
        }

        return $data;
    }

    /**
     * Order a list of field keys neatly: alphabetical, with timestamp
     * fields always pushed to the end.
     */
    public static function orderFieldKeys(array $fields): array
    {
        $timestampKeys = ['created_at', 'updated_at', 'deleted_at'];
        $mainKeys = array_values(array_diff($fields, $timestampKeys));
        sort($mainKeys);

        $ordered = $mainKeys;
        foreach ($timestampKeys as $key) {
            if (in_array($key, $fields, true)) {
                $ordered[] = $key;
            }
        }

        return $ordered;
    }
}