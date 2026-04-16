<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class ProductBatch extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'product_batches';

    protected $fillable = [
        'product_id',
        'batch_code',
        'expiry_date',
        'quantity',
        'status', // 1=active, 2=expired, 3=inactive
    ];

    protected $casts = [
        'product_id' => 'integer',
        'expiry_date' => 'date',
        'quantity' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Status constants for better code readability
     */
    const STATUS_ACTIVE = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_INACTIVE = 3;

    /**
     * Check if batch is expired
     */
    public function isExpired()
    {
        return $this->expiry_date < now();
    }

    /**
     * Check if batch is inactive (quantity is 0)
     */
    public function isInactive()
    {
        return $this->quantity <= 0;
    }

    /**
     * Check if batch is active (has stock and not expired)
     */
    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE && 
               $this->quantity > 0 && 
               !$this->isExpired();
    }

    /**
     * Check if batch has stock
     */
    public function hasStock()
    {
        return $this->quantity > 0;
    }

    /**
     * Get status text attribute
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_INACTIVE => 'Inactive',
            default => 'Unknown'
        };
    }

    /**
     * Get status badge class attribute
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'badge-success',
            self::STATUS_EXPIRED => 'badge-secondary',
            self::STATUS_INACTIVE => 'badge-danger',
            default => 'badge-secondary'
        };
    }

    /**
     * Get status color attribute for UI
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'danger',
            self::STATUS_EXPIRED => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get the used percentage of stock
     */
    public function getUsedPercentageAttribute()
    {
        return 100 - $this->remaining_percentage;
    }

    /**
     * Get days until expiry
     */
    public function getDaysToExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }
        
        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if batch is expiring soon (within 30 days)
     */
    public function isExpiringSoon($days = 30)
    {
        if ($this->isExpired()) {
            return false;
        }
        
        $daysToExpiry = $this->days_to_expiry;
        return $daysToExpiry !== null && $daysToExpiry <= $days;
    }

    /**
     * Scope a query to only include active batches
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('quantity', '>', 0)
                     ->where('expiry_date', '>', now());
    }

    /**
     * Scope a query to only include expired batches
     */
    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere('expiry_date', '<', now());
        });
    }

    /**
     * Scope a query to only include inactive batches (quantity = 0)
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE)
                     ->orWhere('quantity', '<=', 0);
    }

    /**
     * Scope a query to only include batches with stock
     */
    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope a query to order by expiry date (FEFO - First Expiry First Out)
     */
    public function scopeFefo($query)
    {
        return $query->orderBy('expiry_date', 'asc');
    }

    public function warehouseInventoryBalances()
    {
        return $this->hasMany(WarehouseInventoryBalance::class, 'batch_id');
    }

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'batch_id');
    }

    // Batch Code Generation and Parsing
    /**
     * Generate batch code based on format
     */
    public static function generateBatchCode($groupId, $userId, $productCode)
    {
        $year = date('y');
        $fixedDigit = '8';
        $day = date('d');
        $month = date('m');
        
        return strtoupper($groupId) . 
               str_pad($userId, 2, '0', STR_PAD_LEFT) . 
               $year . 
               $fixedDigit . 
               $day . 
               $month . 
               strtoupper($productCode);
    }
    
    /**
     * Parse batch code into components
     */
    public static function parseBatchCode($batchCode)
    {
        if (strlen($batchCode) < 10) {
            return null;
        }
        
        return [
            'group' => substr($batchCode, 0, 1),
            'user_id' => substr($batchCode, 1, 2),
            'year' => substr($batchCode, 3, 2),
            'fixed' => substr($batchCode, 5, 1),
            'day' => substr($batchCode, 6, 2),
            'month' => substr($batchCode, 8, 2),
            'product_code' => substr($batchCode, 10)
        ];
    }
    
    /**
     * Find product by batch code
     */
    public static function findProductByBatchCode($batchCode)
    {
        // First check if batch exists
        $existingBatch = self::where('batch_code', $batchCode)->first();
        if ($existingBatch) {
            return [
                'exists' => true,
                'product' => $existingBatch->product,
                'batch' => $existingBatch
            ];
        }

        // Try to parse the batch code to find product by product_code
        $parsed = self::parseBatchCode($batchCode);
        if ($parsed && isset($parsed['product_code'])) {
            $product = Product::where('unit_code', $parsed['product_code'])->first();
            if ($product) {
                return [
                    'exists' => false,
                    'product' => $product,
                    'batch' => null
                ];
            }
        }

        return null;
    }

    /**
     * Accessor for expiry date
     */
    public function getExpiryDateAttribute($value)
    {
        if ($value) {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        }
        return null;
    }

    /**
     * Get formatted expiry date
     */
    public function getFormattedExpiryDateAttribute()
    {
        if (!$this->expiry_date) {
            return 'N/A';
        }
        
        return \Carbon\Carbon::parse($this->expiry_date)->format('d-m-Y');
    }

    /**
     * Increase batch quantity (stock in)
     */
    public function increaseQuantity($amount, $remark = null, $userId = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        $this->quantity += $amount;
        $this->save();
        
        // Create transaction record
        return $this->inventoryTransactions()->create([
            'type' => 1, // Stock In
            'quantity' => $amount,
            'date' => now(),
            'remark' => $remark,
            'user' => Auth::user()->name ?? 'System',
        ]);
    }

    /**
     * Decrease batch quantity (stock out)
     */
    public function decreaseQuantity($amount, $remark = null, $userId = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if ($amount > $this->quantity) {
            throw new \InvalidArgumentException('Insufficient stock');
        }
        
        $this->quantity -= $amount;
        $this->save();
        
        // Create transaction record
        return $this->inventoryTransactions()->create([
            'type' => 2, // Stock Out
            'quantity' => -$amount,
            'date' => now(),
            'remark' => $remark,
            'user' => Auth::user()->name ?? 'System',
        ]);
    }
}