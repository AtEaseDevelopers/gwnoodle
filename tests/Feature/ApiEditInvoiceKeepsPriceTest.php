<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Invoice line prices are immutable through the driver editInvoice API: the
 * mobile app rebuilds lines from its current price list when editing (e.g. a
 * payment-term change), which silently re-priced invoice I2610/L6/0188 from
 * 4.80 to 5.80. The server must keep each existing line's stored price and
 * ignore the price in the edit payload. Only genuinely new lines use the
 * payload price. Price corrections are admin-only (invoices.updatedetail).
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class ApiEditInvoiceKeepsPriceTest extends TestCase
{
    use DatabaseTransactions;

    private function makeProductWithBatch(float $price): array
    {
        $productId = DB::table('products')->insertGetId([
            'name'   => 'EDITKEEP PROD ' . uniqid(),
            'price'  => $price,
            'cost'   => 0,
            'status' => 1,
        ]);

        $batchId = DB::table('product_batches')->insertGetId([
            'product_id' => $productId,
            'batch_code' => 'EDITKEEP' . strtoupper(substr(uniqid(), -8)),
            'quantity'   => 100,
            'status'     => 1,
        ]);

        return [$productId, $batchId];
    }

    public function test_edit_keeps_stored_price_and_new_lines_use_payload_price(): void
    {
        $session = 'sess_editkeep_' . uniqid();
        $driverId = DB::table('drivers')->insertGetId([
            'name'       => 'EDITKEEP DRIVER ' . uniqid(),
            'employeeid' => 'EK' . strtoupper(substr(uniqid(), -6)),
            'password'   => bcrypt('secret'),
            'session'    => $session,
            'status'     => 1,
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'code'    => 'EK-' . strtoupper(substr(uniqid(), -8)),
            'company' => 'EDITKEEP CUSTOMER',
            'status'  => 1,
        ]);

        [$productA, $batchA] = $this->makeProductWithBatch(5.80);
        [$productB, $batchB] = $this->makeProductWithBatch(9.50);

        // Invoice originally sold product A at 4.80 (not the list price).
        $invoiceId = DB::table('invoices')->insertGetId([
            'invoiceno'   => 'EK/TEST/' . strtoupper(uniqid()),
            'date'        => now(),
            'customer_id' => $customerId,
            'driver_id'   => $driverId,
            'paymentterm' => 1,
            'status'      => 1,
            'created_at'  => now(),
        ]);
        DB::table('invoice_details')->insert([
            'invoice_id'       => $invoiceId,
            'product_id'       => $productA,
            'product_batch_id' => $batchA,
            'quantity'         => 60,
            'price'            => 4.80,
            'totalprice'       => 288,
        ]);

        // The app edits the payment term but re-sends the line re-priced to
        // 5.8 (its current list price), and adds a new line for product B.
        $response = $this->withHeaders(['session' => $session])
            ->postJson('/api/v1/driver/invoice/' . $invoiceId, [
                'customer_id'   => (string) $customerId,
                'paymentterm'   => 2,
                'remark'        => null,
                'invoicedetail' => [
                    ['product_id' => $productA, 'product_batch_id' => $batchA, 'quantity' => 60, 'price' => 5.8],
                    ['product_id' => $productB, 'product_batch_id' => $batchB, 'quantity' => 2, 'price' => 9.5],
                ],
            ]);

        $response->assertStatus(200);

        $lineA = DB::table('invoice_details')
            ->where('invoice_id', $invoiceId)->where('product_id', $productA)->first();
        $lineB = DB::table('invoice_details')
            ->where('invoice_id', $invoiceId)->where('product_id', $productB)->first();

        // Existing line keeps the original sale price, not the payload's 5.8.
        $this->assertSame('4.800', number_format((float) $lineA->price, 3, '.', ''));
        $this->assertSame('288.000', number_format((float) $lineA->totalprice, 3, '.', ''));

        // Genuinely new line uses the payload price.
        $this->assertSame('9.500', number_format((float) $lineB->price, 3, '.', ''));

        // The payment term change itself still applies.
        $this->assertSame(2, (int) DB::table('invoices')->where('id', $invoiceId)->value('paymentterm'));
    }
}
