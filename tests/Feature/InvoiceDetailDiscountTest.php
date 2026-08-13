<?php

namespace Tests\Feature;

use App\Models\InvoiceDetail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the optional, fixed-amount, whole-line per-item discount added to
 * invoice_details (migration 2026_08_13). Net line total is
 * `quantity * price - discount`; discount defaults to 0 so un-discounted lines
 * are unchanged.
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class InvoiceDetailDiscountTest extends TestCase
{
    use DatabaseTransactions;

    /** The discount column must exist as double(10,3). */
    public function test_discount_column_is_three_decimal_double(): void
    {
        $type = DB::select("SHOW COLUMNS FROM `invoice_details` LIKE 'discount'")[0]->Type;
        $this->assertSame('double(10,3)', strtolower($type));
    }

    /** With no discount, the line total is still quantity * price. */
    public function test_line_total_without_discount_is_gross(): void
    {
        $detail = InvoiceDetail::create([
            'invoice_id'       => 999999,
            'product_id'       => 999999,
            'product_batch_id' => 999999,
            'quantity'         => 100,
            'price'            => 0.15,
        ]);

        $fresh = $detail->fresh();
        $this->assertSame('0.000', number_format($fresh->discount, 3, '.', ''));
        // 100 * 0.15 = 15.000
        $this->assertSame('15.000', number_format($fresh->totalprice, 3, '.', ''));
    }

    /** A whole-line discount is subtracted from quantity * price. */
    public function test_line_total_subtracts_whole_line_discount(): void
    {
        $detail = InvoiceDetail::create([
            'invoice_id'       => 999999,
            'product_id'       => 999999,
            'product_batch_id' => 999999,
            'quantity'         => 100,
            'price'            => 0.15,
            'discount'         => 1.50,
        ]);

        $fresh = $detail->fresh();
        $this->assertSame('1.500', number_format($fresh->discount, 3, '.', ''));
        // 100 * 0.15 - 1.50 = 13.500
        $this->assertSame('13.500', number_format($fresh->totalprice, 3, '.', ''));
    }

    /** Editing the discount recomputes the line total. */
    public function test_updating_discount_recomputes_total(): void
    {
        $detail = InvoiceDetail::create([
            'invoice_id'       => 999999,
            'product_id'       => 999999,
            'product_batch_id' => 999999,
            'quantity'         => 10,
            'price'            => 2.000,
            'discount'         => 0,
        ]);

        $this->assertSame('20.000', number_format($detail->fresh()->totalprice, 3, '.', ''));

        $detail->discount = 3.250;
        $detail->save();

        // 10 * 2.000 - 3.250 = 16.750
        $this->assertSame('16.750', number_format($detail->fresh()->totalprice, 3, '.', ''));
    }

    /** A caller passing totalprice cannot bypass the discount netting. */
    public function test_discount_is_applied_even_when_caller_sets_totalprice(): void
    {
        $detail = InvoiceDetail::create([
            'invoice_id'       => 999999,
            'product_id'       => 999999,
            'product_batch_id' => 999999,
            'quantity'         => 100,
            'price'            => 0.15,
            'discount'         => 1.50,
            'totalprice'       => 15.00, // stale gross value from a caller
        ]);

        // boot() recomputes to the net, ignoring the passed gross total.
        $this->assertSame('13.500', number_format($detail->fresh()->totalprice, 3, '.', ''));
    }
}
