<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The mobile addinvoice endpoint must accept an optional per-line fixed-amount
 * discount and store the net line total (quantity * price - discount).
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class ApiAddInvoiceDiscountTest extends TestCase
{
    use DatabaseTransactions;

    public function test_addinvoice_stores_per_line_discount_and_net_total(): void
    {
        $lorryId = 999123;
        $session = 'sess_disc_' . uniqid();
        // drivers.trip_id is an integer column matched against trips.uuid.
        $tripUuid = 999000000 + random_int(1, 899999);

        $driverId = DB::table('drivers')->insertGetId([
            'name'       => 'API DISC DRIVER ' . uniqid(),
            'employeeid' => 'EMP' . strtoupper(substr(uniqid(), -6)),
            'password'   => bcrypt('secret'),
            'session'    => $session,
            'trip_id'    => $tripUuid,
            'status'     => 1,
        ]);

        DB::table('trips')->insert([
            'uuid'      => $tripUuid,
            'driver_id' => $driverId,
            'lorry_id'  => $lorryId,
            'type'      => 1, // Trip::START_TRIP
            'date'      => now(),
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'code'    => 'CUST' . strtoupper(substr(uniqid(), -6)),
            'company' => 'API DISC CUSTOMER ' . uniqid(),
            'status'  => 1,
        ]);

        $productId = DB::table('products')->insertGetId([
            'name'   => 'API DISC PRODUCT ' . uniqid(),
            'price'  => 0.15,
            'cost'   => 0.05,
            'status' => 1,
        ]);

        $batchId = DB::table('product_batches')->insertGetId([
            'product_id' => $productId,
            'batch_code' => 'DISC' . strtoupper(substr(uniqid(), -8)),
            'quantity'   => 500,
            'status'     => 1,
        ]);

        // Stock the lorry so the line goes through the "in inventory" path.
        DB::table('inventory_balances')->insert([
            'lorry_id' => $lorryId,
            'batches'  => json_encode([$batchId => 500]),
        ]);

        $invoiceNo = 'TEST/ADD/DISC/' . $productId;

        $response = $this->withHeader('session', $session)
            ->postJson('/api/v1/driver/invoice', [
                'invoiceno'     => $invoiceNo,
                'date'          => now()->format('Y-m-d'),
                'customer_id'   => $customerId,
                'paymentterm'   => 2, // credit - avoids cash payment side effects
                'remark'        => '',
                'invoicedetail' => [
                    [
                        'product_id'       => $productId,
                        'product_batch_id' => $batchId,
                        'quantity'         => 100,
                        'price'            => 0.15,
                        'discount'         => 1.50,
                    ],
                ],
            ]);

        $response->assertOk();
        $this->assertTrue(
            (bool) $response->json('result'),
            'addinvoice failed: ' . $response->json('message')
        );

        $invoiceId = DB::table('invoices')->where('invoiceno', $invoiceNo)->value('id');
        $this->assertNotNull($invoiceId, 'Invoice was not created.');

        $detail = DB::table('invoice_details')
            ->where('invoice_id', $invoiceId)
            ->where('product_id', $productId)
            ->first();

        $this->assertNotNull($detail, 'Invoice detail was not created.');
        $this->assertSame('1.500', number_format((float) $detail->discount, 3, '.', ''));
        // 100 * 0.15 - 1.50 = 13.500
        $this->assertSame('13.500', number_format((float) $detail->totalprice, 3, '.', ''));
    }
}
