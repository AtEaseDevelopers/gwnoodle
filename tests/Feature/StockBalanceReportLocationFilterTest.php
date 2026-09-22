<?php

namespace Tests\Feature;

use App\Models\ProductBatch;
use App\Services\StockBalanceReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Stock Balance Report's "Location" filter maps straight onto the
 * service's warehouse_id filter: a specific warehouse id restricts the
 * report to that one location, while "All location" (submitted as 0, which
 * is falsy) reports every active warehouse.
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class StockBalanceReportLocationFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_location_filter_scopes_report_to_selected_warehouse(): void
    {
        $suffix = strtoupper(substr(uniqid(), -6));

        $warehouseAId = DB::table('warehouses')->insertGetId([
            'name'       => 'SB WH A ' . $suffix,
            'location'   => 'LOC A ' . $suffix,
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $warehouseBId = DB::table('warehouses')->insertGetId([
            'name'       => 'SB WH B ' . $suffix,
            'location'   => 'LOC B ' . $suffix,
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

        // Same product stocked in both warehouses.
        foreach ([$warehouseAId, $warehouseBId] as $warehouseId) {
            $batchId = DB::table('product_batches')->insertGetId([
                'product_id'   => $productId,
                'warehouse_id' => $warehouseId,
                'batch_code'   => 'SB-' . $warehouseId . '-' . $suffix,
                'quantity'     => 10,
                'status'       => ProductBatch::STATUS_ACTIVE,
                'expiry_date'  => now()->addYear(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::table('warehouse_inventory_balances')->insert([
                'warehouse_id' => $warehouseId,
                'product_id'   => $productId,
                'batch_id'     => $batchId,
                'quantity'     => 10,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $service = new StockBalanceReportService();

        // Selecting one location must exclude the other warehouse entirely.
        $reportA = $service->generateReportOptimized([
            'warehouse_id' => $warehouseAId,
            'product_id'   => $productId,
        ]);

        $warehouseIdsA = collect($reportA['warehouses'])->pluck('warehouse.id')->all();
        $this->assertContains($warehouseAId, $warehouseIdsA, 'Selected warehouse must be present');
        $this->assertNotContains($warehouseBId, $warehouseIdsA, 'Other warehouse must be excluded');

        // "All location" is submitted as 0 (falsy) - both warehouses report.
        $reportAll = $service->generateReportOptimized([
            'warehouse_id' => 0,
            'product_id'   => $productId,
        ]);

        $warehouseIdsAll = collect($reportAll['warehouses'])->pluck('warehouse.id')->all();
        $this->assertContains($warehouseAId, $warehouseIdsAll, 'Warehouse A must be present for All location');
        $this->assertContains($warehouseBId, $warehouseIdsAll, 'Warehouse B must be present for All location');
    }
}
