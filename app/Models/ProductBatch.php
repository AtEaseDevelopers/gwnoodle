<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;

    protected $table = 'product_batches';

    protected $fillable = [
        'product_id',
        'batch_code',
        'expiry_date',
        'quantity',
        'initial_quantity',   
        'status', // 1=active, 2=expired, 3=depleted
    ];

    protected $casts = [
        'product_id' => 'integer',
        'expiry_date' => 'date',
        'quantity' => 'integer',
        'status' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'batch_id');
    }

    /**
     * Check if batch is expired
     */
    public function isExpired()
    {
        return $this->expiry_date < now();
    }

    /**
     * Check if batch has stock
     */
    public function hasStock()
    {
        return $this->quantity > 0;
    }
    
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
}