<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * A pending request to add stock to a product batch. Nothing is applied to the
 * batch quantity, warehouse balance or inventory ledger until an admin approves.
 */
class StockInRequest extends Model
{
    use SoftDeletes;

    public $table = 'stock_in_requests';

    const STATUS_PENDING  = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    const SOURCE_BULK_SCAN    = 'bulk_scan';
    const SOURCE_BATCH_CREATE = 'batch_create';

    public $fillable = [
        'source',
        'warehouse_id',
        'product_id',
        'batch_id',
        'requested_quantity',
        'quantity',
        'remark',
        'status',
        'approval_remark',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'warehouse_id' => 'integer',
        'product_id' => 'integer',
        'batch_id' => 'integer',
        'requested_quantity' => 'integer',
        'quantity' => 'integer',
        'status' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }

    public function batch()
    {
        return $this->belongsTo(\App\Models\ProductBatch::class, 'batch_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id', 'id');
    }

    public function isPending()
    {
        return (int) $this->status === self::STATUS_PENDING;
    }

    public function statusLabel()
    {
        switch ((int) $this->status) {
            case self::STATUS_APPROVED: return 'Approved';
            case self::STATUS_REJECTED: return 'Rejected';
            default: return 'Pending';
        }
    }

    /**
     * Approve this request and apply the stock: increment the batch quantity,
     * increase the warehouse balance and write a stock-in InventoryTransaction.
     * This is the single point where a batch stock-in actually takes effect.
     */
    public function approveAndApply()
    {
        $batch = $this->batch;

        if (empty($batch)) {
            throw new \RuntimeException('Product Batch not found for this request');
        }

        $quantity = (int) $this->quantity;

        // Increment the batch aggregate quantity. Status is never touched
        // here - it's purely manual/informational now, not derived from
        // quantity (see ProductBatch::scopeAvailableForSale()).
        $batch->increment('quantity', $quantity);

        // Increase the warehouse inventory balance (when a warehouse is set)
        if (!empty($this->warehouse_id)) {
            $warehouseInventory = WarehouseInventoryBalance::firstOrCreate(
                [
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $this->product_id,
                    'batch_id' => $this->batch_id,
                ],
                ['quantity' => 0]
            );

            $warehouseInventory->increaseQuantity($quantity);
        }

        // Write the stock-in ledger entry
        InventoryTransaction::create([
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'batch_id' => $this->batch_id,
            'quantity' => $quantity,
            'type' => InventoryTransaction::TYPE_STOCK_IN,
            'remark' => $this->transactionRemark(),
            'date' => now(),
            'user' => Auth::user()->name ?? 'system',
            'stock_received' => 1,
        ]);

        $this->status = self::STATUS_APPROVED;
        $this->reviewed_by = Auth::user()->name ?? 'system';
        $this->reviewed_at = now();
        $this->save();
    }

    /**
     * Build the ledger remark, preserving the original stock-in phrasing.
     */
    protected function transactionRemark()
    {
        $base = trim((string) $this->remark);

        if ($base === '') {
            $base = $this->source === self::SOURCE_BATCH_CREATE
                ? 'Initial stock for batch: ' . optional($this->batch)->batch_code
                : 'Bulk stock in from barcode scan';
        }

        $warehouseName = optional($this->warehouse)->name;

        return $warehouseName
            ? $base . ' - Warehouse: ' . $warehouseName . ' [Approved]'
            : $base . ' [Approved]';
    }
}
