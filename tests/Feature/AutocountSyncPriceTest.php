<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression test: the AutoCount sync (/api/invoice/pending) must send the
 * actual sold price stored on invoice_details, NOT the product master price.
 *
 * The bug: the details query selected `products.price as price` alongside
 * `invoice_details.*`, so the aliased product price clobbered the real sold
 * price. A line sold at 9.50 was synced to AutoCount as the master 10.50.
 */
class AutocountSyncPriceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sync_pending_sends_sold_price_not_product_master_price(): void
    {
        $masterPrice = 10.50; // product master
        $soldPrice   = 9.50;  // driver's negotiated/sold price on the invoice line

        $productId = DB::table('products')->insertGetId([
            'name'   => 'TEST SYNC PRICE PRODUCT',
            'price'  => $masterPrice,
            'cost'   => 6,
            'status' => 1,
        ]);

        $invoiceNo = 'TEST/SYNC/' . $productId;
        $invoiceId = DB::table('invoices')->insertGetId([
            'invoiceno'        => $invoiceNo,
            'date'             => now(),
            'customer_id'      => 999999,
            'paymentterm'      => 1,
            'status'           => 1,          // required by syncPending filter
            'autocount_status' => 'pending',  // required by syncPending filter
            'created_at'       => now(),       // must be >= cut_off_date
        ]);

        DB::table('invoice_details')->insert([
            'invoice_id'       => $invoiceId,
            'product_id'       => $productId,
            'product_batch_id' => 0,
            'quantity'         => 2,
            'price'            => $soldPrice,
            'totalprice'       => 2 * $soldPrice,
        ]);

        $response = $this->getJson('/api/invoice/pending');
        $response->assertStatus(200);

        $invoices = collect($response->json('data'));
        $invoice  = $invoices->firstWhere('invoice_no', $invoiceNo);

        $this->assertNotNull($invoice, 'Seeded invoice was not returned by the sync endpoint.');

        $detail = collect($invoice['details'])->firstWhere('product_id', $productId);
        $this->assertNotNull($detail, 'Seeded invoice detail was not returned.');

        $this->assertEquals(
            $soldPrice,
            (float) $detail['price'],
            'Sync must send the sold price (invoice_details.price), not the product master price.'
        );
        $this->assertNotEquals(
            $masterPrice,
            (float) $detail['price'],
            'Sync leaked the product master price instead of the sold price.'
        );
    }
}
