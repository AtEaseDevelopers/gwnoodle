<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The driver app bills a whole line to one batch even when that batch has
 * less on the lorry than the line's quantity. The server must take the rest
 * from the lorry's other batches of the same product (earliest expiry first)
 * instead of zeroing the requested batch and silently dropping the excess,
 * which left already-sold stock showing on the lorry (Ramen, van JTB 6558,
 * invoice I2610/L2/A044). Anything the lorry can't cover is still invoiced,
 * without a stock deduction.
 *
 * Runs against the shared dev database (DatabaseTransactions); rows roll back.
 */
class ApiInvoiceLorryBatchSplitTest extends TestCase
{
    use DatabaseTransactions;

    private int $lorryId;
    private string $session;
    private int $customerId;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lorryId = 998000 + random_int(1, 999);
        $this->session = 'sess_split_' . uniqid();
        $tripUuid = 998000000 + random_int(1, 899999);

        $driverId = DB::table('drivers')->insertGetId([
            'name'       => 'SPLIT DRIVER ' . uniqid(),
            'employeeid' => 'SP' . strtoupper(substr(uniqid(), -6)),
            'password'   => bcrypt('secret'),
            'session'    => $this->session,
            'trip_id'    => $tripUuid,
            'status'     => 1,
        ]);

        DB::table('trips')->insert([
            'uuid'      => $tripUuid,
            'driver_id' => $driverId,
            'lorry_id'  => $this->lorryId,
            'type'      => 1, // Trip::START_TRIP
            'date'      => now(),
        ]);

        $this->customerId = DB::table('customers')->insertGetId([
            'code'    => 'SP' . strtoupper(substr(uniqid(), -6)),
            'company' => 'SPLIT CUSTOMER ' . uniqid(),
            'status'  => 1,
        ]);

        $this->productId = DB::table('products')->insertGetId([
            'name'   => 'SPLIT RAMEN ' . uniqid(),
            'price'  => 1,
            'cost'   => 0,
            'status' => 1,
        ]);
    }

    private function makeBatch(string $expiry): int
    {
        return DB::table('product_batches')->insertGetId([
            'product_id'  => $this->productId,
            'batch_code'  => 'SPLIT' . strtoupper(substr(uniqid(), -8)),
            'expiry_date' => $expiry,
            'quantity'    => 0,
            'status'      => 3, // warehouse ran out: inactive, but still on the lorry
        ]);
    }

    private function stockLorry(array $batches): void
    {
        DB::table('inventory_balances')->insert([
            'lorry_id' => $this->lorryId,
            'batches'  => json_encode($batches),
        ]);
    }

    private function sell(int $batchId, int $quantity, float $discount = 0): int
    {
        $invoiceNo = 'TEST/SPLIT/' . strtoupper(uniqid());

        $response = $this->withHeader('session', $this->session)
            ->postJson('/api/v1/driver/invoice', [
                'invoiceno'     => $invoiceNo,
                'date'          => now()->format('Y-m-d'),
                'customer_id'   => $this->customerId,
                'paymentterm'   => 2, // credit - avoids cash payment side effects
                'remark'        => '',
                'invoicedetail' => [[
                    'product_id'       => $this->productId,
                    'product_batch_id' => $batchId,
                    'quantity'         => $quantity,
                    'price'            => 1,
                    'discount'         => $discount,
                ]],
            ]);

        $response->assertOk();
        $this->assertTrue((bool) $response->json('result'), 'addinvoice failed: ' . $response->json('message'));

        return (int) DB::table('invoices')->where('invoiceno', $invoiceNo)->value('id');
    }

    private function lorryBatches(): array
    {
        return json_decode(DB::table('inventory_balances')->where('lorry_id', $this->lorryId)->value('batches'), true) ?: [];
    }

    private function lines(int $invoiceId)
    {
        return DB::table('invoice_details')->where('invoice_id', $invoiceId)->orderBy('id')->get();
    }

    public function test_oversized_line_is_split_across_lorry_batches_by_expiry(): void
    {
        $requested = $this->makeBatch('2026-12-01');
        $laterExpiry = $this->makeBatch('2026-12-20');
        $earlierExpiry = $this->makeBatch('2026-12-10');
        $this->stockLorry([$requested => 60, $laterExpiry => 60, $earlierExpiry => 60]);

        $invoiceId = $this->sell($requested, 150, 5);

        $lines = $this->lines($invoiceId);
        $this->assertSame(
            [[$requested, 60], [$earlierExpiry, 60], [$laterExpiry, 30]],
            $lines->map(fn ($l) => [(int) $l->product_batch_id, (int) $l->quantity])->all()
        );
        $this->assertTrue($lines->every(fn ($l) => (bool) $l->deducted_from_inventory));

        // Whole-line discount stays on one line only.
        $this->assertSame([5.0, 0.0, 0.0], $lines->map(fn ($l) => (float) $l->discount)->all());
        $this->assertSame(145.0, (float) $lines->sum('totalprice'));

        // Lorry stock is reduced by exactly what was sold.
        $this->assertSame([(string) $laterExpiry => 30], $this->lorryBatches());
    }

    public function test_quantity_beyond_lorry_stock_is_invoiced_without_deduction(): void
    {
        $requested = $this->makeBatch('2026-12-01');
        $other = $this->makeBatch('2026-12-10');
        $this->stockLorry([$requested => 60, $other => 60]);

        $invoiceId = $this->sell($requested, 200);

        $lines = $this->lines($invoiceId);
        $this->assertSame(
            [[$requested, 60, true], [$other, 60, true], [$requested, 80, false]],
            $lines->map(fn ($l) => [(int) $l->product_batch_id, (int) $l->quantity, (bool) $l->deducted_from_inventory])->all()
        );
        $this->assertSame(200, (int) $lines->sum('quantity'));
        $this->assertSame([], $this->lorryBatches());
    }
}
