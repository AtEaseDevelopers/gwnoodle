<?php

namespace Tests\Feature;

use App\Services\StockTransferReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every Stock Transfer Report filter must narrow the output to exactly the
 * matching transfers - never drop matching rows, never leak others.
 *
 * Transfers are written the way WarehouseController::transferStock does
 * (outgoing row, then incoming row). Rows are scoped to test-only
 * warehouses so other transfers on the shared dev database don't interfere.
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class StockTransferReportFilterTest extends TestCase
{
    use DatabaseTransactions;

    private int $no23;
    private int $no18;
    private int $other;
    private int $productA;
    private int $productB;
    private int $batchA;
    private int $batchB;
    private string $codeA;

    protected function setUp(): void
    {
        parent::setUp();

        $warehouse = fn ($name) => DB::table('warehouses')->insertGetId([
            'name' => $name . ' ' . uniqid(), 'status' => 'active',
        ]);
        $this->no23 = $warehouse('TRF NO 23');
        $this->no18 = $warehouse('TRF NO 18');
        $this->other = $warehouse('TRF OTHER');

        $product = fn ($name) => DB::table('products')->insertGetId([
            'name' => $name . ' ' . uniqid(), 'price' => 1, 'cost' => 0, 'status' => 1,
        ]);
        $this->productA = $product('TRF A');
        $this->productB = $product('TRF B');

        $this->codeA = 'TRFA' . strtoupper(uniqid());
        $batch = fn ($productId, $code) => DB::table('product_batches')->insertGetId([
            'product_id' => $productId, 'batch_code' => $code, 'expiry_date' => '2026-12-31',
            'quantity' => 0, 'status' => 1,
        ]);
        $this->batchA = $batch($this->productA, $this->codeA);
        $this->batchB = $batch($this->productB, 'TRFB' . strtoupper(uniqid()));

        $this->transfer($this->no23, $this->no18, $this->productA, $this->batchA, 10, '2026-11-10 09:00:00');
        $this->transfer($this->no23, $this->no18, $this->productB, $this->batchB, 20, '2026-11-12 09:00:00');
        $this->transfer($this->no18, $this->no23, $this->productA, $this->batchA, 30, '2026-11-14 09:00:00');
        $this->transfer($this->no23, $this->other, $this->productA, $this->batchA, 40, '2026-11-20 09:00:00');

        // Manual adjustment: also type 4, must never show up.
        DB::table('inventory_transactions')->insert([
            'warehouse_id' => $this->no23, 'product_id' => $this->productA, 'batch_id' => $this->batchA,
            'quantity' => -5, 'type' => 4, 'remark' => 'Manual adjustment: 50 → 45',
            'date' => '2026-11-11 09:00:00', 'user' => 'tester',
        ]);
    }

    private function transfer(int $from, int $to, int $productId, int $batchId, int $qty, string $date): void
    {
        foreach ([[$from, -$qty, 'Stock transferred to warehouse: -[X]'], [$to, $qty, 'Stock received from warehouse: -[Y]  ']] as [$wh, $q, $remark]) {
            DB::table('inventory_transactions')->insert([
                'warehouse_id' => $wh, 'product_id' => $productId, 'batch_id' => $batchId,
                'quantity' => $q, 'type' => 4, 'remark' => $remark, 'date' => $date, 'user' => 'tester',
            ]);
        }
    }

    /** Quantities of this test's transfers in the report, in date order. */
    private function quantities(string $from, string $to, array $filters = []): array
    {
        $report = (new StockTransferReportService())->generateReport($from, $to, $filters);
        $mine = [];
        foreach ($report['items'] as $item) {
            if (str_starts_with($item['from_location'], 'TRF ')) {
                $mine[] = $item['quantity'];
            }
        }
        return $mine;
    }

    public function test_filters_narrow_to_exactly_the_matching_transfers(): void
    {
        // No filters / "All location" (0) / ALL (%): every transfer, no adjustment.
        $this->assertSame([10, 20, 30, 40], $this->quantities('2026-11-01', '2026-11-30'));
        $this->assertSame([10, 20, 30, 40], $this->quantities('2026-11-01', '2026-11-30', [
            'from_warehouse_id' => '0', 'to_warehouse_id' => '0', 'product_id' => ['%'], 'batch_id' => null,
        ]));

        // From / To location, alone and together (the "NO 23 to NO 18" case).
        $this->assertSame([10, 20, 40], $this->quantities('2026-11-01', '2026-11-30', ['from_warehouse_id' => $this->no23]));
        $this->assertSame([10, 20], $this->quantities('2026-11-01', '2026-11-30', ['to_warehouse_id' => $this->no18]));
        $this->assertSame([10, 20], $this->quantities('2026-11-01', '2026-11-30', [
            'from_warehouse_id' => $this->no23, 'to_warehouse_id' => $this->no18,
        ]));

        // Date range: whole days, both ends inclusive; reversed dates still work.
        $this->assertSame([10, 20], $this->quantities('2026-11-10', '2026-11-12'));
        $this->assertSame([10, 20], $this->quantities('2026-11-12', '2026-11-10'));

        // Product: by id; ALL left ticked next to a product still filters.
        $this->assertSame([20], $this->quantities('2026-11-01', '2026-11-30', ['product_id' => [(string) $this->productB]]));
        $this->assertSame([20], $this->quantities('2026-11-01', '2026-11-30', ['product_id' => ['%', (string) $this->productB]]));

        // Batch: by code.
        $this->assertSame([10, 30, 40], $this->quantities('2026-11-01', '2026-11-30', ['batch_id' => [$this->codeA]]));

        // Everything combined.
        $this->assertSame([10], $this->quantities('2026-11-01', '2026-11-30', [
            'from_warehouse_id' => $this->no23, 'to_warehouse_id' => $this->no18,
            'product_id' => [(string) $this->productA], 'batch_id' => [$this->codeA],
        ]));
    }

    public function test_report_header_and_totals(): void
    {
        $report = (new StockTransferReportService())->generateReport('2026-11-01', '2026-11-30', [
            'from_warehouse_id' => $this->no23, 'to_warehouse_id' => $this->no18,
        ]);

        $this->assertStringStartsWith('TRF NO 23', $report['from_location']);
        $this->assertStringStartsWith('TRF NO 18', $report['to_location']);
        $this->assertSame(30, $report['total_quantity']);
        $this->assertSame($this->codeA, $report['items'][0]['batch_code']);
    }
}
