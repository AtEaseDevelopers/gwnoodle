<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Invoice saves from one driver must run one at a time. The app's offline
 * sync fired the same sale twice at the same moment (I2611/L3/0067, invoices
 * 10781/10782): both requests ran the duplicate check before either had
 * committed, so both saved and the lorry stock was deducted twice.
 *
 * The other session holding the lock stands in for a concurrent request from
 * the same driver that is still saving.
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class DriverInvoiceLockTest extends TestCase
{
    use DatabaseTransactions;

    private int $driverId;
    private string $session;
    private int $customerId;
    private int $productId;
    private int $batchId;

    protected function setUp(): void
    {
        parent::setUp();

        $lorryId = 997000 + random_int(1, 999);
        $this->session = 'sess_lock_' . uniqid();
        $tripUuid = 997000000 + random_int(1, 899999);

        $this->driverId = DB::table('drivers')->insertGetId([
            'name'         => 'LOCK DRIVER ' . uniqid(),
            'invoice_code' => 'LK' . random_int(10, 99),
            'employeeid' => 'LK' . strtoupper(substr(uniqid(), -6)),
            'password'   => bcrypt('secret'),
            'session'    => $this->session,
            'trip_id'    => $tripUuid,
            'status'     => 1,
        ]);

        DB::table('trips')->insert([
            'uuid'      => $tripUuid,
            'driver_id' => $this->driverId,
            'lorry_id'  => $lorryId,
            'type'      => 1, // Trip::START_TRIP
            'date'      => now(),
        ]);

        $this->customerId = DB::table('customers')->insertGetId([
            'code'    => 'LK' . strtoupper(substr(uniqid(), -6)),
            'company' => 'LOCK CUSTOMER ' . uniqid(),
            'status'  => 1,
        ]);

        $this->productId = DB::table('products')->insertGetId([
            'name'   => 'LOCK PROD ' . uniqid(),
            'price'  => 1,
            'cost'   => 0,
            'status' => 1,
        ]);

        $this->batchId = DB::table('product_batches')->insertGetId([
            'product_id'  => $this->productId,
            'batch_code'  => 'LOCK' . strtoupper(substr(uniqid(), -8)),
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity'    => 0,
            'status'      => 1,
        ]);

        DB::table('inventory_balances')->insert([
            'lorry_id' => $lorryId,
            'batches'  => json_encode([$this->batchId => 100]),
        ]);

        config(['app.driver_invoice_lock_timeout' => 1]);

        // A second, independent database session - the default connection is
        // inside the test's transaction.
        config(['database.connections.lock_holder' => config('database.connections.' . config('database.default'))]);
    }

    protected function tearDown(): void
    {
        DB::connection('lock_holder')->select('SELECT RELEASE_ALL_LOCKS()');
        DB::purge('lock_holder');

        parent::tearDown();
    }

    private function otherSession()
    {
        return DB::connection('lock_holder');
    }

    private function lockName(): string
    {
        return 'driver_invoice_' . $this->driverId;
    }

    private function sale(string $invoiceNo): array
    {
        return [
            'invoiceno'     => $invoiceNo,
            'date'          => now()->format('Y-m-d'),
            'customer_id'   => $this->customerId,
            'paymentterm'   => 2, // credit - avoids cash payment side effects
            'remark'        => '',
            'invoicedetail' => [[
                'product_id'       => $this->productId,
                'product_batch_id' => $this->batchId,
                'quantity'         => 5,
                'price'            => 1,
            ]],
        ];
    }

    public function test_invoice_waits_while_another_save_for_the_same_driver_is_running(): void
    {
        $this->otherSession()->select('SELECT GET_LOCK(?, 0)', [$this->lockName()]);
        $invoiceNo = 'TEST/LOCK/' . strtoupper(uniqid());

        $response = $this->withHeader('session', $this->session)
            ->postJson('/api/v1/driver/invoice', $this->sale($invoiceNo));

        $response->assertStatus(409);
        $this->assertFalse(DB::table('invoices')->where('invoiceno', $invoiceNo)->exists(),
            'No invoice may be saved while another save for the same driver holds the lock.');
    }

    public function test_bulk_sync_waits_while_another_save_for_the_same_driver_is_running(): void
    {
        $this->otherSession()->select('SELECT GET_LOCK(?, 0)', [$this->lockName()]);
        $invoiceNo = 'TEST/LOCK/' . strtoupper(uniqid());

        $response = $this->withHeader('session', $this->session)
            ->postJson('/api/v1/driver/bulk-add-invoice', ['invoices' => [$this->sale($invoiceNo)]]);

        $response->assertStatus(409);
        $this->assertFalse(DB::table('invoices')->where('invoiceno', $invoiceNo)->exists());
    }

    public function test_lock_is_released_once_the_save_finishes(): void
    {
        $invoiceNo = 'TEST/LOCK/' . strtoupper(uniqid());

        $response = $this->withHeader('session', $this->session)
            ->postJson('/api/v1/driver/invoice', $this->sale($invoiceNo));

        $response->assertOk();
        $this->assertTrue((bool) $response->json('result'), 'addinvoice failed: ' . $response->json('message'));

        $acquired = $this->otherSession()->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$this->lockName()])->acquired;
        $this->assertSame(1, (int) $acquired, 'The driver lock must be released after the save, or the next save would hang.');
    }

    public function test_other_drivers_are_not_blocked(): void
    {
        $this->otherSession()->select('SELECT GET_LOCK(?, 0)', ['driver_invoice_' . ($this->driverId + 100000)]);
        $invoiceNo = 'TEST/LOCK/' . strtoupper(uniqid());

        $response = $this->withHeader('session', $this->session)
            ->postJson('/api/v1/driver/invoice', $this->sale($invoiceNo));

        $response->assertOk();
        $this->assertTrue(DB::table('invoices')->where('invoiceno', $invoiceNo)->exists());
    }
}
