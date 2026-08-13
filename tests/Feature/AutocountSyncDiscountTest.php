<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the per-line discount in the AutoCount sync payload (/api/invoice/pending):
 *
 *  - An un-discounted line keeps the current behaviour exactly: unit price is
 *    derived from the line total and discount is 0 (regression guard).
 *  - A discounted line sends the GROSS stored unit price plus the whole-line
 *    discount amount, so AutoCount computes (Qty x UnitPrice) - Discount and
 *    lands on the same net line total.
 */
class AutocountSyncDiscountTest extends TestCase
{
    use DatabaseTransactions;

    private function seedInvoiceWithLine(array $line): string
    {
        $productId = DB::table('products')->insertGetId([
            'name'   => 'TEST DISCOUNT PRODUCT',
            'price'  => 0.15,
            'cost'   => 0.05,
            'status' => 1,
        ]);

        $invoiceNo = 'TEST/DISC/' . $productId;
        $invoiceId = DB::table('invoices')->insertGetId([
            'invoiceno'        => $invoiceNo,
            'date'             => now(),
            'customer_id'      => 999999,
            'paymentterm'      => 1,
            'status'           => 1,
            'autocount_status' => 'pending',
            'created_at'       => now(),
        ]);

        DB::table('invoice_details')->insert(array_merge([
            'invoice_id'       => $invoiceId,
            'product_id'       => $productId,
            'product_batch_id' => 0,
        ], $line));

        return $invoiceNo;
    }

    private function detailFor(string $invoiceNo): array
    {
        $response = $this->getJson('/api/invoice/pending');
        $response->assertStatus(200);

        $invoice = collect($response->json('data'))->firstWhere('invoice_no', $invoiceNo);
        $this->assertNotNull($invoice, 'Seeded invoice was not returned by the sync endpoint.');

        return collect($invoice['details'])->first();
    }

    /** Regression: an un-discounted line still sends the derived unit price and discount 0. */
    public function test_undiscounted_line_sends_derived_price_and_zero_discount(): void
    {
        // qty 100 x 0.15 = 15.00, no discount.
        $invoiceNo = $this->seedInvoiceWithLine([
            'quantity'   => 100,
            'price'      => 0.15,
            'discount'   => 0,
            'totalprice' => 15.00,
        ]);

        $detail = $this->detailFor($invoiceNo);

        $this->assertSame('0.150', number_format((float) $detail['price'], 3, '.', ''));
        $this->assertSame('0.000', number_format((float) $detail['discount'], 3, '.', ''));
        // AutoCount: 100 x 0.150 - 0 = 15.000
    }

    /** A discounted line sends the gross unit price plus the whole-line discount. */
    public function test_discounted_line_sends_gross_price_and_discount(): void
    {
        // qty 100 x 0.15 - 1.50 = 13.50
        $invoiceNo = $this->seedInvoiceWithLine([
            'quantity'   => 100,
            'price'      => 0.15,
            'discount'   => 1.50,
            'totalprice' => 13.50,
        ]);

        $detail = $this->detailFor($invoiceNo);

        // Gross unit price, NOT the net-derived 0.135.
        $this->assertSame('0.150', number_format((float) $detail['price'], 3, '.', ''));
        $this->assertSame('1.500', number_format((float) $detail['discount'], 3, '.', ''));

        // AutoCount reproduces the net: 100 x 0.150 - 1.50 = 13.50
        $net = 100 * (float) $detail['price'] - (float) $detail['discount'];
        $this->assertSame('13.500', number_format($net, 3, '.', ''));
    }
}
