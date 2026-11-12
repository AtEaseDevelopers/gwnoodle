<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Drivers may change prices when editing an invoice: the driver editInvoice
 * API saves each line at the price in the edit payload, for existing and new
 * lines alike.
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class ApiEditInvoicePriceTest extends TestCase
{
    use DatabaseTransactions;

    private function makeProductWithBatch(float $price): array
    {
        $productId = DB::table('products')->insertGetId([
            'name'   => 'EDITPRICE PROD ' . uniqid(),
            'price'  => $price,
            'cost'   => 0,
            'status' => 1,
        ]);

        $batchId = DB::table('product_batches')->insertGetId([
            'product_id' => $productId,
            'batch_code' => 'EDITPRICE' . strtoupper(substr(uniqid(), -8)),
            'quantity'   => 100,
            'status'     => 1,
        ]);

        return [$productId, $batchId];
    }

    public function test_edit_saves_payload_price_for_existing_and_new_lines(): void
    {
        $session = 'sess_editprice_' . uniqid();
        $driverId = DB::table('drivers')->insertGetId([
            'name'       => 'EDITPRICE DRIVER ' . uniqid(),
            'employeeid' => 'EP' . strtoupper(substr(uniqid(), -6)),
            'password'   => bcrypt('secret'),
            'session'    => $session,
            'status'     => 1,
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'code'    => 'EP-' . strtoupper(substr(uniqid(), -8)),
            'company' => 'EDITPRICE CUSTOMER',
            'status'  => 1,
        ]);

        [$productA, $batchA] = $this->makeProductWithBatch(5.80);
        [$productB, $batchB] = $this->makeProductWithBatch(9.50);

        // Invoice originally sold product A at 4.80.
        $invoiceId = DB::table('invoices')->insertGetId([
            'invoiceno'   => 'EP/TEST/' . strtoupper(uniqid()),
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

        // The driver re-prices product A to 5.8, changes the payment term
        // and adds a new line for product B.
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

        // Existing line takes the edited price.
        $this->assertSame('5.800', number_format((float) $lineA->price, 3, '.', ''));
        $this->assertSame('348.000', number_format((float) $lineA->totalprice, 3, '.', ''));

        // New line uses the payload price.
        $this->assertSame('9.500', number_format((float) $lineB->price, 3, '.', ''));

        // The payment term change itself still applies.
        $this->assertSame(2, (int) DB::table('invoices')->where('id', $invoiceId)->value('paymentterm'));
    }
}
