<?php

namespace Tests\Feature;

use App\Models\InventoryTransaction;
use App\Services\StockCardService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The stock card report must abbreviate a Stock Adjustment transaction
 * as "SA". Previously TYPE_STOCK_ADJUSTMENT was missing from the type
 * match and fell through to the "N/A" default.
 *
 * Runs against the shared dev database (DatabaseTransactions).
 */
class StockCardStockAdjustmentTypeTest extends TestCase
{
    use DatabaseTransactions;

    private function makeProductId(): int
    {
        return DB::table('products')->insertGetId([
            'name'   => 'SA CARD PROD ' . uniqid(),
            'price'  => 0,
            'cost'   => 0,
            'status' => 1,
        ]);
    }

    public function test_stock_adjustment_transaction_is_abbreviated_as_sa(): void
    {
        $productId = $this->makeProductId();

        $dateFrom = '2026-08-01';
        $dateTo   = '2026-08-31';

        InventoryTransaction::create([
            'product_id' => $productId,
            'quantity'   => 10,
            'type'       => InventoryTransaction::TYPE_STOCK_ADJUSTMENT,
            'date'       => '2026-08-10 12:00:00',
            'remark'     => 'test adjustment',
            'user'       => 'tester',
        ]);

        $report = (new StockCardService())->generateReport([$productId], $dateFrom, $dateTo);

        $this->assertNotNull($report);
        $transactions = $report['products'][0]['transactions'];
        $this->assertCount(1, $transactions);
        $this->assertSame('SA', $transactions[0]['type']);
        $this->assertNotSame('N/A', $transactions[0]['type']);
    }
}
