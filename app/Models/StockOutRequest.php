<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * A pending request to deduct stock from a product batch in a warehouse.
 * Nothing is applied to the batch quantity, warehouse balance or inventory
 * ledger until an admin approves — mirroring StockInRequest.
 */
class StockOutRequest extends Model
{
    use SoftDeletes;

    public $table = 'stock_out_requests';

    const STATUS_PENDING  = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    const SOURCE_WAREHOUSE_MODAL = 'warehouse_modal';
    const SOURCE_BARCODE_SCAN    = 'barcode_scan';
    const SOURCE_MOBILE_APP      = 'mobile_app';

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
     * Approve this request and apply the stock-out: decrement the warehouse
     * balance and batch master quantity and write a stock-out InventoryTransaction.
     * This is the single point where a batch stock-out actually takes effect.
     *
     * Both the warehouse balance and batch rows are locked with lockForUpdate so
     * concurrent approvals cannot oversell (each waits for the other's commit and
     * re-checks the live quantity). MUST be called inside a DB transaction.
     */
    public function approveAndApply()
    {
        $quantity = (int) $this->quantity;

        if ($quantity <= 0) {
            throw new \RuntimeException('Invalid quantity for this request');
        }

        // Lock the batch master row first.
        $batch = ProductBatch::whereKey($this->batch_id)->lockForUpdate()->first();

        if (empty($batch)) {
            throw new \RuntimeException('Product Batch not found for this request');
        }

        // Lock the warehouse balance row; without one there is nothing to deduct.
        $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $this->warehouse_id)
            ->where('batch_id', $this->batch_id)
            ->lockForUpdate()
            ->first();

        if (empty($warehouseInventory) || (int) $warehouseInventory->quantity < $quantity) {
            $available = $warehouseInventory ? (int) $warehouseInventory->quantity : 0;
            throw new \RuntimeException('Insufficient warehouse stock. Available: ' . $available . ', Requested: ' . $quantity);
        }

        if ((int) ($batch->quantity ?? 0) < $quantity) {
            throw new \RuntimeException('Insufficient batch stock. Available: ' . (int) ($batch->quantity ?? 0) . ', Requested: ' . $quantity);
        }

        // Deduct from the warehouse balance and the batch master quantity.
        // Status is never touched here - it's purely manual/informational
        // now, not derived from quantity (see
        // ProductBatch::scopeAvailableForSale()).
        $warehouseInventory->decreaseQuantity($quantity);
        $batch->decreaseQuantity($quantity);

        // Write the stock-out ledger entry (negative quantity).
        InventoryTransaction::create([
            'warehouse_id' => $this->warehouse_id,
            'product_id' => $this->product_id,
            'batch_id' => $this->batch_id,
            'quantity' => -$quantity,
            'type' => InventoryTransaction::TYPE_STOCK_OUT,
            'remark' => $this->transactionRemark(),
            'date' => now(),
            'user' => Auth::user()->name ?? 'system',
        ]);

        $this->status = self::STATUS_APPROVED;
        $this->reviewed_by = Auth::user()->name ?? 'system';
        $this->reviewed_at = now();
        $this->save();
    }

    /**
     * Build the ledger remark, preserving the original stock-out phrasing.
     */
    protected function transactionRemark()
    {
        $base = trim((string) $this->remark);

        if ($base === '') {
            $base = 'Stock out';
        }

        $warehouseName = optional($this->warehouse)->name;

        return $warehouseName
            ? $base . ' - Warehouse: ' . $warehouseName . ' [Approved]'
            : $base . ' [Approved]';
    }
}
