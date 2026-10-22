<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\DriverController;
use App\Models\Driver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The driver-API duplicate-invoice guard only treats a submission as a
 * duplicate when an identical invoice (same driver, customer, trip, products
 * and quantities) was created within the last 10 minutes. After that, the
 * same sale can be created again (a genuine repeat order later in the day).
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class DuplicateInvoiceWindowTest extends TestCase
{
    use DatabaseTransactions;

    private Driver $driver;
    private int $customerId;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();

        $driverId = DB::table('drivers')->insertGetId([
            'name'       => 'DUPWIN DRIVER ' . uniqid(),
            'employeeid' => 'DW' . strtoupper(substr(uniqid(), -6)),
            'password'   => bcrypt('secret'),
            'session'    => 'sess_dupwin_' . uniqid(),
            'trip_id'    => random_int(900000000, 999999999),
            'status'     => 1,
        ]);
        $this->driver = Driver::find($driverId);

        $this->customerId = DB::table('customers')->insertGetId([
            'code'    => 'DW-' . strtoupper(substr(uniqid(), -8)),
            'company' => 'DUPWIN CUSTOMER',
            'status'  => 1,
        ]);

        $this->productId = DB::table('products')->insertGetId([
            'name'   => 'DUPWIN PROD ' . uniqid(),
            'price'  => 1,
            'cost'   => 0,
            'status' => 1,
        ]);
    }

    private function makeInvoiceCreatedMinutesAgo(int $minutes): int
    {
        $createdAt = now()->subMinutes($minutes);

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoiceno'   => 'DW/TEST/' . strtoupper(uniqid()),
            'date'        => $createdAt,
            'customer_id' => $this->customerId,
            'driver_id'   => $this->driver->id,
            'trip_uuid'   => $this->driver->trip_id,
            'paymentterm' => 1,
            'status'      => 1,
            'created_at'  => $createdAt,
        ]);

        DB::table('invoice_details')->insert([
            'invoice_id'       => $invoiceId,
            'product_id'       => $this->productId,
            'product_batch_id' => 999999,
            'quantity'         => 30,
            'price'            => 1,
            'totalprice'       => 30,
        ]);

        return $invoiceId;
    }

    private function findDuplicate()
    {
        $method = new \ReflectionMethod(DriverController::class, 'findDuplicateInvoice');
        $method->setAccessible(true);

        return $method->invoke(
            app(DriverController::class),
            $this->driver,
            $this->customerId,
            [['product_id' => $this->productId, 'quantity' => 30]]
        );
    }

    public function test_identical_invoice_within_10_minutes_is_a_duplicate(): void
    {
        $invoiceId = $this->makeInvoiceCreatedMinutesAgo(5);

        $duplicate = $this->findDuplicate();

        $this->assertNotNull($duplicate, 'Identical sale 5 minutes ago must be treated as a duplicate.');
        $this->assertSame($invoiceId, $duplicate->id);
    }

    public function test_identical_invoice_older_than_10_minutes_is_not_a_duplicate(): void
    {
        $this->makeInvoiceCreatedMinutesAgo(15);

        $this->assertNull($this->findDuplicate(), 'Identical sale 15 minutes ago must be allowed again.');
    }
}
