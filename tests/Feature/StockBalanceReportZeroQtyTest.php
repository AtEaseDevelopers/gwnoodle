<?php

namespace Tests\Feature;

use App\Models\ProductBatch;
use App\Services\StockBalanceReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Zero-balance batches (warehouse balance exactly 0) are hidden from the
 * stock balance report by default - nobody needs to see stock nobody has.
 * Depleted batches keep their warehouse_inventory_balances row at quantity 0
 * (rows are decremented in place, never deleted), so they'd otherwise
 * clutter the report unless explicitly requested via show_zero_stock.
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class StockBalanceReportZeroQtyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_report_hides_zero_balance_batches_by_default(): void
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

        // Default call (no show_zero_stock) - the zero-balance batch must
        // not appear at all.
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
        $this->assertNotContains('SB-ZERO-' . $suffix, $batchCodes, 'Zero-balance batch must be hidden by default');
        $this->assertSame(10, (int) $product['total_quantity']);

        // With show_zero_stock explicitly requested, the zero-balance batch
        // reappears - the capability isn't removed, just opt-in now.
        $reportWithZero = $service->generateReportOptimized([
            'warehouse_id'    => $warehouseId,
            'product_id'      => $productId,
            'show_zero_stock' => true,
        ]);

        $productWithZero = collect($reportWithZero['warehouses'])
            ->firstWhere('warehouse.id', $warehouseId)['products'] ?? [];
        $productWithZero = collect($productWithZero)->firstWhere('product_id', $productId);

        $this->assertNotNull($productWithZero, 'Product should be present when show_zero_stock is set');

        $batchCodesWithZero = collect($productWithZero['batches'])->pluck('batch_no')->all();
        $this->assertContains('SB-ZERO-' . $suffix, $batchCodesWithZero, 'Zero-balance batch must be listed when show_zero_stock is set');

        $zeroBatch = collect($productWithZero['batches'])->firstWhere('batch_no', 'SB-ZERO-' . $suffix);
        $this->assertSame(0, (int) $zeroBatch['quantity'], 'Zero-balance batch quantity must be 0');
    }
}
