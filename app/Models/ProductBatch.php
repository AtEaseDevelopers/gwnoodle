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
     * Fixed filler digit that separates the year from the day in a
     * system-generated batch code (group+user+YY+FIXED+DD+MM+productCode).
     */
    const BATCH_CODE_FIXED_DIGIT = '8';

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
        $fixedDigit = self::BATCH_CODE_FIXED_DIGIT;
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
     * Decode the expiry date embedded in a system-generated batch code.
     *
     * Layout: group(1) user(2) YY(2) FIXED(1) DD(2) MM(2) productCode.
     * Returns a 'Y-m-d' string, or null when the code is not date-encoded
     * (legacy/imported codes) or the embedded date is not a real calendar date.
     */
    public static function expiryDateFromCode($batchCode)
    {
        if (!is_string($batchCode) || strlen($batchCode) < 10) {
            return null;
        }

        // System codes carry the fixed filler digit right after the year;
        // its absence means the code was not generated with an embedded date.
        if (substr($batchCode, 5, 1) !== self::BATCH_CODE_FIXED_DIGIT) {
            return null;
        }

        $yy = substr($batchCode, 3, 2);
        $dd = substr($batchCode, 6, 2);
        $mm = substr($batchCode, 8, 2);

        if (!ctype_digit($yy . $dd . $mm)) {
            return null;
        }

        $year = 2000 + (int) $yy;
        $month = (int) $mm;
        $day = (int) $dd;

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
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

        // Treat an un-initialized (NULL) master quantity as 0 so batches seeded
        // straight into a warehouse balance don't break on the first stock-in.
        $this->quantity = ($this->quantity ?? 0) + $amount;
        $this->save();

    }

    /**
     * Decrease batch quantity (stock out)
     */
    public function decreaseQuantity($amount, $remark = null, $userId = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        // A NULL master quantity is an un-initialized batch, not "0 in stock".
        // Coerce to 0 so the check below reports the real shortfall instead of
        // failing NULL arithmetic with a misleading generic message.
        $current = $this->quantity ?? 0;

        if ($amount > $current) {
            throw new \InvalidArgumentException('Insufficient stock');
        }

        $this->quantity = $current - $amount;
        $this->save();

    }
}