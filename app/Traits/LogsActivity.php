<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    /**
     * Boot the trait
     */
    public static function bootLogsActivity()
    {
        // Log created events
        static::created(function ($model) {
            $model->logActivity('create', 'Created new record');
        });

        // Log updated events
        static::updated(function ($model) {
            // Get the changes
            $oldData = [];
            $newData = [];
            
            foreach ($model->getChanges() as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $oldData[$field] = $model->getOriginal($field);
                    $newData[$field] = $newValue;
                }
            }
            
            if (!empty($oldData)) {
                $model->logActivity('update', 'Updated record', $oldData, $newData);
            }
        });

        // Log deleted events
        static::deleted(function ($model) {
            $model->logActivity('delete', 'Deleted record', $model->toArray(), null);
        });
    }

    /**
     * Log an activity
     */
    public function logActivity($action, $description = null, $oldData = null, $newData = null)
    {
        $user = Auth::user();
        
        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'module' => $this->getModuleName(),
            'table_name' => $this->getTable(),
            'record_id' => $this->getKey(),
            'description' => $description ?? $this->generateDescription($action),
            'old_data' => $oldData ?? $this->getOriginal(),
            'new_data' => $newData ?? $this->getAttributes(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent()
        ]);
    }

    /**
     * Get the module name from the model
     */
    protected function getModuleName()
    {
        $className = class_basename($this);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    /**
     * Generate a description for the activity
     */
    protected function generateDescription($action)
    {
        $moduleName = ucwords(str_replace('_', ' ', $this->getModuleName()));
        $identifier = $this->getLogIdentifier();
        
        return "{$action} {$moduleName}: {$identifier}";
    }

    /**
     * Get an identifier for the model (override in your models)
     */
    public function getLogIdentifier()
    {
        // Try to use name, title, or fall back to ID
        return $this->name ?? $this->title ?? $this->email ?? "ID: {$this->getKey()}";
    }
}