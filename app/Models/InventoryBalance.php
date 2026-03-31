<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryBalance extends Model
{
    use HasFactory;

    public $table = 'inventory_balances';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'lorry_id',
        'batches' // JSON field storing batch_id => quantity
    ];

    protected $casts = [
        'id' => 'integer',
        'lorry_id' => 'integer',
        'batches' => 'array' // Automatically cast JSON to array
    ];

    public static $rules = [
        'lorry_id' => 'required|unique:inventory_balances,lorry_id', // One record per lorry
        'batches' => 'required|array',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    public function lorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'lorry_id', 'id');
    }

    /**
     * Get all batches with their product details
     */
    public function getBatchesWithDetailsAttribute()
    {
        if (empty($this->batches)) {
            return [];
        }
        $batchIds = array_keys($this->batches);
        $batches = \App\Models\ProductBatch::with('product')
            ->whereIn('id', $batchIds)
            ->get();
        $result = [];
        foreach ($batches as $batch) {
            $result[] = [
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'product_name' => $batch->product->name ?? 'N/A',
                'unit_code' => $batch->product->unit_code ?? 'N/A',
                'expiry_date' => $batch->expiry_date,
                'quantity' => $this->batches[$batch->id] ?? 0
            ];
        }

        return $result;
    }

    /**
     * Get quantity for a specific batch
     */
    public function getBatchQuantity($batchId)
    {
        return $this->batches[$batchId] ?? 0;
    }

    /**
     * Add or update batch quantity
     */
    public function updateBatchQuantity($batchId, $quantity, $operation = 'add')
    {
        $batches = $this->batches ?? [];
        
        if ($operation === 'add') {
            $batches[$batchId] = ($batches[$batchId] ?? 0) + $quantity;
        } elseif ($operation === 'subtract') {
            if (isset($batches[$batchId])) {
                $batches[$batchId] = max(0, $batches[$batchId] - $quantity);
                if ($batches[$batchId] == 0) {
                    unset($batches[$batchId]); // Remove batch if quantity becomes 0
                }
            }
        } elseif ($operation === 'set') {
            if ($quantity > 0) {
                $batches[$batchId] = $quantity;
            } else {
                unset($batches[$batchId]);
            }
        }

        $this->batches = $batches;
        return $this->save();
    }

    /**
     * Get total quantity for a specific product across all batches in this lorry
     */
    public function getProductTotalQuantity($productId)
    {
        if (empty($this->batches)) {
            return 0;
        }

        $batchIds = array_keys($this->batches);
        $batches = \App\Models\ProductBatch::whereIn('id', $batchIds)
            ->where('product_id', $productId)
            ->get();

        $total = 0;
        foreach ($batches as $batch) {
            $total += $this->batches[$batch->id] ?? 0;
        }

        return $total;
    }

    /**
     * Get total quantity across all batches
     */
    public function getTotalQuantityAttribute()
    {
        return array_sum($this->batches ?? []);
    }

    /**
     * Get total number of batches
     */
    public function getTotalBatchesAttribute()
    {
        return count($this->batches ?? []);
    }

    /**
     * Get expiring soon batches
     */
    public function getExpiringBatchesAttribute($days = 30)
    {
        if (empty($this->batches)) {
            return [];
        }

        $expiring = [];
        $batchIds = array_keys($this->batches);
        $batches = \App\Models\ProductBatch::whereIn('id', $batchIds)
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now())
            ->get();

        foreach ($batches as $batch) {
            $expiring[] = [
                'batch_code' => $batch->batch_code,
                'expiry_date' => $batch->expiry_date->format('d-m-Y'),
                'quantity' => $this->batches[$batch->id] ?? 0,
                'days_left' => now()->diffInDays($batch->expiry_date, false)
            ];
        }

        return $expiring;
    }

    /**
     * Check if lorry has any expired batches
     */
    public function hasExpiredBatchesAttribute()
    {
        if (empty($this->batches)) {
            return false;
        }

        $batchIds = array_keys($this->batches);
        return \App\Models\ProductBatch::whereIn('id', $batchIds)
            ->where('expiry_date', '<', now())
            ->exists();
    }

    /**
     * Get summary text for the batches
     */
    public function getBatchesSummaryAttribute()
    {
        if (empty($this->batches)) {
            return 'No batches';
        }

        $totalBatches = count($this->batches);
        $totalQuantity = array_sum($this->batches);
        
        return $totalBatches . ' batches, ' . $totalQuantity . ' total units';
    }

     public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }
    
}