<?php

namespace Tests\Feature;

use App\Models\ProductBatch;
use App\Services\StockBalanceReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The stock balance report must list zero-balance batches (warehouse balance
 * exactly 0), not only batches that still hold stock. Depleted batches keep
 * their warehouse_inventory_balances row at quantity 0 (rows are decremented in
 * place, never deleted), so the report should surface them.
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class StockBalanceReportZeroQtyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_report_includes_zero_balance_batches(): void
    {
        $suffix = strtoupper(substr(uniqid(), -6));

        $warehouseId = DB::table('warehouses')->insertGetId([
            'name'       => 'SB WH ' . $suffix,
            'location'   => 'SB LOC',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'name'       => 'SB PROD ' . $suffix,
            'unit_code'  => 'SB' . $suffix,
            'status'     => 1,
            'cost'       => 2.50,
            'price'      => 5.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Batch A: still holds stock in the warehouse.
        $batchStockedId = DB::table('product_batches')->insertGetId([
            'product_id'  => $productId,
            'warehouse_id' => $warehouseId,
            'batch_code'  => 'SB-STOCK-' . $suffix,
            'quantity'    => 10,
            'status'      => ProductBatch::STATUS_ACTIVE,
            'expiry_date' => now()->addYear(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Batch B: fully depleted — warehouse balance is exactly 0.
        $batchZeroId = DB::table('product_batches')->insertGetId([
            'product_id'  => $productId,
            'warehouse_id' => $warehouseId,
            'batch_code'  => 'SB-ZERO-' . $suffix,
            'quantity'    => 0,
            'status'      => ProductBatch::STATUS_ACTIVE,
            'expiry_date' => now()->addYear(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('warehouse_inventory_balances')->insert([
            [
                'warehouse_id' => $warehouseId,
                'product_id'   => $productId,
                'batch_id'     => $batchStockedId,
                'quantity'     => 10,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'warehouse_id' => $warehouseId,
                'product_id'   => $productId,
                'batch_id'     => $batchZeroId,
                'quantity'     => 0,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        $service = new StockBalanceReportService();
        $report = $service->generateReportOptimized([
            'warehouse_id' => $warehouseId,
            'product_id'   => $productId,
        ]);

        $warehouse = collect($report['warehouses'])
            ->firstWhere('warehouse.id', $warehouseId);

        $this->assertNotNull($warehouse, 'Warehouse should be present in the report');

        $product = collect($warehouse['products'])
            ->firstWhere('product_id', $productId);

        $this->assertNotNull($product, 'Product should be present in the report');

        $batchCodes = collect($product['batches'])->pluck('batch_no')->all();

        $this->assertContains('SB-STOCK-' . $suffix, $batchCodes, 'Stocked batch must be listed');
        $this->assertContains('SB-ZERO-' . $suffix, $batchCodes, 'Zero-balance batch must be listed');

        $zeroBatch = collect($product['batches'])->firstWhere('batch_no', 'SB-ZERO-' . $suffix);
        $this->assertSame(0, (int) $zeroBatch['quantity'], 'Zero-balance batch quantity must be 0');

        // Totals only count real stock, so the zero batch does not inflate them.
        $this->assertSame(10, (int) $product['total_quantity']);
    }
}
