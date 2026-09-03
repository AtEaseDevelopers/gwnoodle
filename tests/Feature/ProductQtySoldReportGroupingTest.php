<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\DailySalesReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Product Qty Sold report must show one row per product record. Grouping by
 * product NAME merged same-name products with different codes into one row,
 * so all but one code vanished from the report (the "ramen/s001 missing" bug).
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class ProductQtySoldReportGroupingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_same_name_products_with_different_codes_get_separate_rows(): void
    {
        $name = 'QTYSOLD RAMEN ' . uniqid();

        $productA = DB::table('products')->insertGetId([
            'name' => $name, 'unit_code' => 's001', 'price' => 1, 'cost' => 0, 'status' => 1,
        ]);
        $productB = DB::table('products')->insertGetId([
            'name' => $name, 'unit_code' => 's002', 'price' => 1, 'cost' => 0, 'status' => 1,
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoiceno'   => 'QTY/TEST/' . strtoupper(uniqid()),
            'date'        => now(),
            'customer_id' => 999999,
            'status'      => Invoice::STATUS_COMPLETED,
            'created_at'  => now(),
        ]);

        DB::table('invoice_details')->insert([
            ['invoice_id' => $invoiceId, 'product_id' => $productA, 'quantity' => 5, 'price' => 1],
            ['invoice_id' => $invoiceId, 'product_id' => $productB, 'quantity' => 3, 'price' => 1],
        ]);

        $report = (new DailySalesReportService())
            ->generateReportByDateRange(now()->format('Y-m-d'), now()->format('Y-m-d'));

        $rows = collect($report['products'])->where('product_name', $name);

        $this->assertCount(2, $rows, 'Same-name products must not be merged into one row.');
        $this->assertSame(5, (int) $rows->firstWhere('product_code', 's001')['quantity']);
        $this->assertSame(3, (int) $rows->firstWhere('product_code', 's002')['quantity']);
    }
}
