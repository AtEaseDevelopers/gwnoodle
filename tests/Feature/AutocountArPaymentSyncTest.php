<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * AutoCount AR Payment (Official Receipt) sync.
 *
 * Covers the /api/payment/pending grouping + eligibility rules and the
 * /api/payment/update writeback, plus the manual web "Sync" release.
 */
class AutocountArPaymentSyncTest extends TestCase
{
    use DatabaseTransactions;

    private int $customerId;
    private string $customerCode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerCode = 'TESTAR' . random_int(1000, 9999);
        $this->customerId = DB::table('customers')->insertGetId([
            'code'        => $this->customerCode,
            'company'     => 'AR PAYMENT TEST CUSTOMER',
            'paymentterm' => 1,
            'status'      => 1,
        ]);
    }

    /** Create a synced (or not) invoice and return its id. */
    private function makeInvoice(int $paymentterm, string $acStatus = 'success'): int
    {
        return DB::table('invoices')->insertGetId([
            'invoiceno'        => 'AR/TEST/' . Str::upper(Str::random(8)),
            'date'             => now(),
            'customer_id'      => $this->customerId,
            'paymentterm'      => $paymentterm,
            'status'           => 1,
            'autocount_status' => $acStatus,
            'created_at'       => now(),
        ]);
    }

    /** Create an approved payment row in a batch. */
    private function makePayment(string $batchId, int $invoiceId, float $amount, int $type, string $acStatus, array $extra = []): int
    {
        return DB::table('invoice_payments')->insertGetId(array_merge([
            'payment_batch_id' => $batchId,
            'invoice_id'       => $invoiceId,
            'type'             => $type,
            'customer_id'      => $this->customerId,
            'amount'           => $amount,
            'status'           => 1,
            'autocount_status' => $acStatus,
            'chequeno'         => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ], $extra));
    }

    public function test_non_credit_batch_is_grouped_into_one_ar_payment(): void
    {
        $batch = (string) Str::uuid();
        $invA = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH);
        $invB = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH);
        $invNoA = DB::table('invoices')->where('id', $invA)->value('invoiceno');
        $invNoB = DB::table('invoices')->where('id', $invB)->value('invoiceno');

        $this->makePayment($batch, $invA, 500, 1, InvoicePayment::AC_PENDING, ['chequeno' => 'CHQ-1']);
        $this->makePayment($batch, $invB, 300, 1, InvoicePayment::AC_PENDING, ['chequeno' => 'CHQ-1']);

        $res = $this->getJson('/api/payment/pending')->assertStatus(200);

        $entry = collect($res->json('data'))->firstWhere('batch_id', $batch);
        $this->assertNotNull($entry, 'Batch should be returned by pending endpoint.');

        // One entry, two knock-offs, total = sum of the two rows.
        $this->assertEquals($this->customerCode, $entry['customer_code']);
        $this->assertEquals(800.0, (float) $entry['total']);
        $this->assertEquals('CHQ-1', $entry['cheque_no']);
        $this->assertCount(2, $entry['knockoffs']);

        $byInvoice = collect($entry['knockoffs'])->keyBy('invoice_no');
        $this->assertEquals(500.0, (float) $byInvoice[$invNoA]['amount']);
        $this->assertEquals(300.0, (float) $byInvoice[$invNoB]['amount']);
        $this->assertNull($entry['payment_no'], 'First sync has no OR doc number yet.');
    }

    public function test_credit_batch_on_hold_is_excluded(): void
    {
        $batch = (string) Str::uuid();
        $inv = $this->makeInvoice(Invoice::PAYMENT_TERM_CREDIT);
        $this->makePayment($batch, $inv, 200, 2, InvoicePayment::AC_HOLD);

        $res = $this->getJson('/api/payment/pending')->assertStatus(200);
        $this->assertNull(collect($res->json('data'))->firstWhere('batch_id', $batch));
    }

    public function test_batch_with_unsynced_invoice_is_excluded(): void
    {
        $batch = (string) Str::uuid();
        $synced   = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH, 'success');
        $unsynced = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH, 'pending');
        $this->makePayment($batch, $synced, 100, 1, InvoicePayment::AC_PENDING);
        $this->makePayment($batch, $unsynced, 100, 1, InvoicePayment::AC_PENDING);

        $res = $this->getJson('/api/payment/pending')->assertStatus(200);
        $this->assertNull(
            collect($res->json('data'))->firstWhere('batch_id', $batch),
            'A batch cannot sync while any of its invoices is not yet in AutoCount.'
        );
    }

    public function test_update_writes_back_to_all_rows_in_batch(): void
    {
        $batch = (string) Str::uuid();
        $invA = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH);
        $invB = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH);
        $p1 = $this->makePayment($batch, $invA, 500, 1, InvoicePayment::AC_PENDING);
        $p2 = $this->makePayment($batch, $invB, 300, 1, InvoicePayment::AC_PENDING);

        $this->postJson('/api/payment/update', [
            'batch_id'         => $batch,
            'payment_no'       => 'OR-0001',
            'doc_id'           => 12345,
            'autocount_status' => 'success',
        ])->assertStatus(200)->assertJson(['status' => 'ok']);

        foreach ([$p1, $p2] as $pid) {
            $row = DB::table('invoice_payments')->where('id', $pid)->first();
            $this->assertEquals('success', $row->autocount_status);
            $this->assertEquals('OR-0001', $row->payment_no);
            $this->assertEquals(12345, $row->doc_id);
        }
    }

    public function test_failed_update_requeues_batch_once_then_stays_failed(): void
    {
        $batch = (string) Str::uuid();
        $inv = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH);
        $pid = $this->makePayment($batch, $inv, 100, 1, InvoicePayment::AC_PENDING, ['autocount_auto_retried' => 0]);

        // First failure -> auto requeued to pending.
        $this->postJson('/api/payment/update', ['batch_id' => $batch, 'autocount_status' => 'failed'])
            ->assertStatus(200);
        $this->assertEquals('pending', DB::table('invoice_payments')->where('id', $pid)->value('autocount_status'));

        // Second failure -> stays failed.
        $this->postJson('/api/payment/update', ['batch_id' => $batch, 'autocount_status' => 'failed'])
            ->assertStatus(200);
        $this->assertEquals('failed', DB::table('invoice_payments')->where('id', $pid)->value('autocount_status'));
    }

    public function test_manual_sync_endpoint_releases_held_credit_batch(): void
    {
        $batch = (string) Str::uuid();
        $invA = $this->makeInvoice(Invoice::PAYMENT_TERM_CREDIT);
        $invB = $this->makeInvoice(Invoice::PAYMENT_TERM_CREDIT);
        $pA = $this->makePayment($batch, $invA, 200, 2, InvoicePayment::AC_HOLD);
        $pB = $this->makePayment($batch, $invB, 300, 2, InvoicePayment::AC_HOLD);

        // Held -> excluded from auto pending.
        $res = $this->getJson('/api/payment/pending')->assertStatus(200);
        $this->assertNull(collect($res->json('data'))->firstWhere('batch_id', $batch));

        // A user clicks "Sync" on ONE row -> the whole batch is released.
        $this->actingAs($this->makeAdmin())
            ->post('/invoicePayments/sync-autocount/' . encrypt($pA))
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        foreach ([$pA, $pB] as $pid) {
            $this->assertEquals(
                InvoicePayment::AC_PENDING,
                DB::table('invoice_payments')->where('id', $pid)->value('autocount_status')
            );
        }

        // Now the batch appears in pending.
        $res = $this->getJson('/api/payment/pending')->assertStatus(200);
        $this->assertNotNull(collect($res->json('data'))->firstWhere('batch_id', $batch));
    }

    public function test_mass_sync_endpoint_releases_batches_of_selected_payments(): void
    {
        $batch = (string) Str::uuid();
        $invA = $this->makeInvoice(Invoice::PAYMENT_TERM_CREDIT);
        $invB = $this->makeInvoice(Invoice::PAYMENT_TERM_CREDIT);
        $pA = $this->makePayment($batch, $invA, 200, 2, InvoicePayment::AC_HOLD);
        $pB = $this->makePayment($batch, $invB, 300, 2, InvoicePayment::AC_HOLD);

        // Select only ONE row -> its whole batch is released.
        $this->actingAs($this->makeAdmin())
            ->post('/invoicePayments/massSyncAutocount', ['ids' => [$pA]])
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        foreach ([$pA, $pB] as $pid) {
            $this->assertEquals(
                InvoicePayment::AC_PENDING,
                DB::table('invoice_payments')->where('id', $pid)->value('autocount_status')
            );
        }
    }

    public function test_mass_sync_endpoint_requires_selection(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/invoicePayments/massSyncAutocount', ['ids' => []])
            ->assertStatus(422);
    }

    public function test_cancelling_a_payment_via_edit_drops_it_from_the_sync_queue(): void
    {
        $batch = (string) Str::uuid();
        $inv = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH);
        $pid = $this->makePayment($batch, $inv, 100, 1, InvoicePayment::AC_PENDING);

        // Edit the payment and flip its status to Cancelled (2).
        $this->actingAs($this->makeAdmin())
            ->patch(route('invoicePayments.update', encrypt($pid)), [
                'customer_id' => $this->customerId,
                'invoice_id'  => [$inv],
                'type'        => 1,
                'amount'      => 100,
                'status'      => 2,
                'remark'      => '',
            ]);

        $row = DB::table('invoice_payments')->where('id', $pid)->first();
        $this->assertEquals(2, $row->status);
        // No longer queued -> the plugin poll (which only takes 'pending') skips it.
        $this->assertEquals(InvoicePayment::AC_SKIPPED, $row->autocount_status);

        $res = $this->getJson('/api/payment/pending')->assertStatus(200);
        $this->assertNull(collect($res->json('data'))->firstWhere('batch_id', $batch));
    }

    public function test_cancelling_a_payment_keeps_a_successful_autocount_sync_intact(): void
    {
        $batch = (string) Str::uuid();
        $inv = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH);
        $pid = $this->makePayment($batch, $inv, 100, 1, InvoicePayment::AC_SUCCESS);

        $this->actingAs($this->makeAdmin())
            ->patch(route('invoicePayments.update', encrypt($pid)), [
                'customer_id' => $this->customerId,
                'invoice_id'  => [$inv],
                'type'        => 1,
                'amount'      => 100,
                'status'      => 2,
                'remark'      => '',
            ]);

        // Already in AutoCount -> keep the 'success' record rather than hiding it.
        $this->assertEquals(
            InvoicePayment::AC_SUCCESS,
            DB::table('invoice_payments')->where('id', $pid)->value('autocount_status')
        );
    }

    public function test_mass_cancel_drops_pending_rows_from_the_sync_queue(): void
    {
        $batch = (string) Str::uuid();
        $inv = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH);
        $pending = $this->makePayment($batch, $inv, 100, 1, InvoicePayment::AC_PENDING);
        $synced  = $this->makePayment($batch, $inv, 200, 1, InvoicePayment::AC_SUCCESS);

        $this->actingAs($this->makeAdmin())
            ->post('/invoicePayments/massupdatestatus', ['ids' => [$pending, $synced], 'status' => 2]);

        $this->assertEquals(
            InvoicePayment::AC_SKIPPED,
            DB::table('invoice_payments')->where('id', $pending)->value('autocount_status')
        );
        // A row already synced to AutoCount keeps its 'success' record.
        $this->assertEquals(
            InvoicePayment::AC_SUCCESS,
            DB::table('invoice_payments')->where('id', $synced)->value('autocount_status')
        );
    }

    public function test_manual_sync_endpoint_rejects_unapproved_payment(): void
    {
        $batch = (string) Str::uuid();
        $inv = $this->makeInvoice(Invoice::PAYMENT_TERM_CREDIT);
        $pid = DB::table('invoice_payments')->insertGetId([
            'payment_batch_id' => $batch,
            'invoice_id'       => $inv,
            'type'             => 2,
            'customer_id'      => $this->customerId,
            'amount'           => 100,
            'status'           => 0, // not approved
            'autocount_status' => InvoicePayment::AC_HOLD,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->actingAs($this->makeAdmin())
            ->post('/invoicePayments/sync-autocount/' . encrypt($pid))
            ->assertStatus(422);
    }

    public function test_store_auto_syncs_single_noncredit_but_holds_multi_invoice(): void
    {
        $invA = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH, 'pending');
        $invB = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH, 'pending');
        $admin = $this->makeAdmin();

        // Single non-credit invoice -> auto (pending).
        $this->actingAs($admin)->post(route('invoicePayments.store'), [
            'type' => 1, 'customer_id' => $this->customerId, 'amount' => 100,
            'invoice_id' => [$invA],
        ]);
        $this->assertEquals(
            InvoicePayment::AC_PENDING,
            InvoicePayment::where('invoice_id', $invA)->value('autocount_status')
        );

        // One payment across TWO invoices -> held for manual sync.
        $this->actingAs($admin)->post(route('invoicePayments.store'), [
            'type' => 1, 'customer_id' => $this->customerId, 'amount' => 300,
            'invoice_id' => [$invA, $invB],
        ]);
        $multi = InvoicePayment::where('invoice_id', $invB)->latest('id')->first();
        $this->assertEquals(InvoicePayment::AC_HOLD, $multi->autocount_status);
    }

    public function test_store_rejects_payment_whose_invoice_belongs_to_another_customer(): void
    {
        // A second customer owns the invoice, but the payment is booked under the
        // first customer -> the exact shape that fails AutoCount sync with
        // "belongs to debtor X, not Y". The guard must block it before any row.
        $otherCustomerId = DB::table('customers')->insertGetId([
            'code'        => 'TESTAR' . random_int(1000, 9999),
            'company'     => 'OTHER AR CUSTOMER',
            'paymentterm' => 1,
            'status'      => 1,
        ]);
        $foreignInvoice = DB::table('invoices')->insertGetId([
            'invoiceno'        => 'AR/TEST/' . Str::upper(Str::random(8)),
            'date'             => now(),
            'customer_id'      => $otherCustomerId,
            'paymentterm'      => Invoice::PAYMENT_TERM_CASH,
            'status'           => 1,
            'autocount_status' => 'success',
            'created_at'       => now(),
        ]);

        $this->actingAs($this->makeAdmin())->post(route('invoicePayments.store'), [
            'type'        => 1,
            'customer_id' => $this->customerId,       // booked under a DIFFERENT customer
            'amount'      => 73,
            'invoice_id'  => [$foreignInvoice],
        ]);

        // Nothing was written for the mismatched invoice.
        $this->assertDatabaseMissing('invoice_payments', [
            'invoice_id'  => $foreignInvoice,
            'customer_id' => $this->customerId,
        ]);
    }

    public function test_store_allows_payment_when_invoice_belongs_to_the_customer(): void
    {
        $invoice = $this->makeInvoice(Invoice::PAYMENT_TERM_CASH, 'success');

        $this->actingAs($this->makeAdmin())->post(route('invoicePayments.store'), [
            'type'        => 1,
            'customer_id' => $this->customerId,
            'amount'      => 100,
            'invoice_id'  => [$invoice],
        ]);

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id'  => $invoice,
            'customer_id' => $this->customerId,
        ]);
    }

    public function test_driver_addpayment_rejects_invoice_belonging_to_another_customer(): void
    {
        // Invoice owned by a SECOND customer, but the driver posts the payment
        // under the first customer -> AutoCount would reject on sync. The API
        // guard must return 400 and write no payment row.
        $otherCustomerId = DB::table('customers')->insertGetId([
            'code'        => 'TESTAR' . random_int(1000, 9999),
            'company'     => 'DRIVER OTHER CUSTOMER',
            'paymentterm' => 1,
            'status'      => 1,
        ]);
        $foreignInvoice = DB::table('invoices')->insertGetId([
            'invoiceno'        => 'AR/TEST/' . Str::upper(Str::random(8)),
            'date'             => now(),
            'customer_id'      => $otherCustomerId,
            'paymentterm'      => Invoice::PAYMENT_TERM_CASH,
            'status'           => 1,
            'autocount_status' => 'pending',
            'created_at'       => now(),
        ]);

        $session = 'sess-' . Str::random(20);
        DB::table('drivers')->insert([
            'name'       => 'GUARD TEST DRIVER',
            'employeeid' => 'EMP' . random_int(1000, 9999),
            'password'   => bcrypt('secret'),
            'phone'      => '0100000000',
            'session'    => $session,
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->withHeader('session', $session)
            ->postJson('/api/v1/driver/invoicepayment', [
                'customer_id' => $this->customerId,   // booked under a DIFFERENT customer
                'type'        => 1,
                'amount'      => 73,
                'invoice_ids' => [$foreignInvoice],
            ]);

        $res->assertStatus(400);
        $this->assertDatabaseMissing('invoice_payments', ['invoice_id' => $foreignInvoice]);
    }

    private function makeAdmin(): User
    {
        $user = User::create([
            'name'     => 'AR PAY ADMIN ' . uniqid(),
            'email'    => 'arpay_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
        ]);
        $user->assignRole('admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
