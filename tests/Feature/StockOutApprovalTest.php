<?php

namespace Tests\Feature;

use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\StockOutRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventoryBalance;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Stock-out now mirrors stock-in: every stock-out is queued as a pending
 * StockOutRequest and nothing is deducted until an admin approves. Approval is
 * the single point where the warehouse balance, batch master quantity and the
 * inventory ledger are mutated, under row locks to prevent oversell.
 *
 * Runs against the shared dev database (DatabaseTransactions), so fixtures are
 * created per-test and assertions target those specific rows.
 */
class StockOutApprovalTest extends TestCase
{
    use DatabaseTransactions;

    private function makeAdmin(): User
    {
        $user = User::create([
            'name'     => 'SO ADMIN ' . uniqid(),
            'email'    => 'so_admin_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole('admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function makeUserWithSession(): User
    {
        return User::create([
            'name'     => 'SO DRIVER ' . uniqid(),
            'email'    => 'so_driver_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
            'session'  => 'sess_' . uniqid(),
        ]);
    }

    private function makeBatch(int $startingQty): ProductBatch
    {
        $productId = DB::table('products')->insertGetId([
            'name'   => 'SO PROD ' . uniqid(),
            'price'  => 0,
            'cost'   => 0,
            'status' => 1,
        ]);

        return ProductBatch::create([
            'product_id' => $productId,
            'batch_code' => 'SO/' . uniqid(),
            'quantity'   => $startingQty,
            'status'     => ProductBatch::STATUS_ACTIVE,
        ]);
    }

    private function makeWarehouse(): Warehouse
    {
        return Warehouse::create([
            'name'              => 'SO WH ' . uniqid(),
            'status'            => 1,
            'stock_out_enabled' => 1,
        ]);
    }

    /**
     * Seed a warehouse balance so the entry-point soft check passes.
     */
    private function seedBalance(Warehouse $wh, ProductBatch $batch, int $qty): WarehouseInventoryBalance
    {
        return WarehouseInventoryBalance::create([
            'warehouse_id' => $wh->id,
            'product_id'   => $batch->product_id,
            'batch_id'     => $batch->id,
            'quantity'     => $qty,
        ]);
    }

    private function makeRequest(Warehouse $wh, ProductBatch $batch, int $qty, int $status = StockOutRequest::STATUS_PENDING): StockOutRequest
    {
        return StockOutRequest::create([
            'source'             => StockOutRequest::SOURCE_WAREHOUSE_MODAL,
            'warehouse_id'       => $wh->id,
            'product_id'         => $batch->product_id,
            'batch_id'           => $batch->id,
            'requested_quantity' => $qty,
            'quantity'           => $qty,
            'status'             => $status,
        ]);
    }

    // ---- Entry points enqueue, never deduct ------------------------------

    public function test_web_warehouse_modal_queues_pending_request_without_deducting(): void
    {
        $batch = $this->makeBatch(10);
        $wh    = $this->makeWarehouse();
        $this->seedBalance($wh, $batch, 10);

        $this->actingAs($this->makeAdmin())
            ->post(route('warehouses.stock-out'), [
                'warehouse_id' => $wh->id,
                'product_id'   => $batch->product_id,
                'batch_id'     => $batch->id,
                'quantity'     => 6,
                'remarks'      => 'Spoiled units',
            ])
            ->assertRedirect();

        $request = StockOutRequest::where('batch_id', $batch->id)->first();
        $this->assertNotNull($request);
        $this->assertSame(StockOutRequest::STATUS_PENDING, (int) $request->status);
        $this->assertSame(6, (int) $request->quantity);

        // Nothing applied yet.
        $this->assertSame(10, (int) $batch->fresh()->quantity);
        $this->assertSame(10, (int) WarehouseInventoryBalance::find($this->seedBalanceId($wh, $batch))->quantity);
        $this->assertFalse(
            InventoryTransaction::where('batch_id', $batch->id)
                ->where('type', InventoryTransaction::TYPE_STOCK_OUT)
                ->exists()
        );
    }

    public function test_api_stock_out_queues_pending_request_without_deducting(): void
    {
        $user  = $this->makeUserWithSession();
        $batch = $this->makeBatch(10);
        $wh    = $this->makeWarehouse();
        $this->seedBalance($wh, $batch, 10);

        $response = $this->withHeader('session', $user->session)
            ->postJson('/api/v1/driver/stock-out-product-batch', [
                'batch_code'   => $batch->batch_code,
                'quantity'     => 4,
                'warehouse_id' => $wh->id,
                'remark'       => 'Damaged',
            ]);

        $response->assertOk()->assertJson(['result' => true]);
        $this->assertStringContainsString('submitted for approval', $response->json('message'));

        $request = StockOutRequest::where('batch_id', $batch->id)
            ->where('requested_by', $user->name)
            ->first();
        $this->assertNotNull($request);
        $this->assertSame(StockOutRequest::STATUS_PENDING, (int) $request->status);
        $this->assertSame(4, (int) $request->quantity);

        $this->assertSame(10, (int) $batch->fresh()->quantity);
    }

    // ---- Approval applies the deduction under lock -----------------------

    public function test_approve_deducts_warehouse_batch_and_writes_ledger(): void
    {
        $batch   = $this->makeBatch(10);
        $wh      = $this->makeWarehouse();
        $balance = $this->seedBalance($wh, $batch, 10);
        $request = $this->makeRequest($wh, $batch, 6);

        $this->actingAs($this->makeAdmin())
            ->post(route('stockOutRequests.approve', $request->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(StockOutRequest::STATUS_APPROVED, (int) $request->fresh()->status);
        $this->assertSame(4, (int) $batch->fresh()->quantity);
        $this->assertSame(4, (int) $balance->fresh()->quantity);

        $ledger = InventoryTransaction::where('batch_id', $batch->id)
            ->where('type', InventoryTransaction::TYPE_STOCK_OUT)
            ->first();
        $this->assertNotNull($ledger);
        $this->assertSame(-6, (int) $ledger->quantity);
    }

    public function test_approve_does_not_touch_status_when_depleted(): void
    {
        // Status is manual/informational only now - depleting a batch to 0
        // must never auto-flip it to inactive (that would make it
        // impossible to tell a genuinely-depleted batch apart from one an
        // admin deliberately deactivated, and driver-side availability is
        // quantity-based, not status-based, precisely so this can't happen).
        $batch   = $this->makeBatch(5);
        $wh      = $this->makeWarehouse();
        $this->seedBalance($wh, $batch, 5);
        $request = $this->makeRequest($wh, $batch, 5);

        $startingStatus = (int) $batch->status;

        $this->actingAs($this->makeAdmin())
            ->post(route('stockOutRequests.approve', $request->id))
            ->assertOk();

        $fresh = $batch->fresh();
        $this->assertSame(0, (int) $fresh->quantity);
        $this->assertSame($startingStatus, (int) $fresh->status);
        $this->assertNotSame(ProductBatch::STATUS_INACTIVE, (int) $fresh->status);
    }

    // ---- Oversell guard (the HIGH fix) -----------------------------------

    public function test_approve_is_blocked_when_stock_dropped_below_request(): void
    {
        $batch   = $this->makeBatch(10);
        $wh      = $this->makeWarehouse();
        $balance = $this->seedBalance($wh, $batch, 10);
        $request = $this->makeRequest($wh, $batch, 8);

        // Simulate a concurrent deduction landing first: balance now below the request.
        $balance->update(['quantity' => 5]);
        $batch->update(['quantity' => 5]);

        $this->actingAs($this->makeAdmin())
            ->post(route('stockOutRequests.approve', $request->id))
            ->assertStatus(500);

        // Request stays pending, nothing further deducted.
        $this->assertSame(StockOutRequest::STATUS_PENDING, (int) $request->fresh()->status);
        $this->assertSame(5, (int) $balance->fresh()->quantity);
        $this->assertSame(5, (int) $batch->fresh()->quantity);
    }

    // ---- Reject & authorization ------------------------------------------

    public function test_reject_requires_remark_and_applies_no_stock(): void
    {
        $batch   = $this->makeBatch(10);
        $wh      = $this->makeWarehouse();
        $balance = $this->seedBalance($wh, $batch, 10);
        $request = $this->makeRequest($wh, $batch, 6);

        $admin = $this->makeAdmin();

        // Missing remark -> 422, still pending.
        $this->actingAs($admin)
            ->post(route('stockOutRequests.reject', $request->id))
            ->assertStatus(422);
        $this->assertSame(StockOutRequest::STATUS_PENDING, (int) $request->fresh()->status);

        // With remark -> rejected, no deduction.
        $this->actingAs($admin)
            ->post(route('stockOutRequests.reject', $request->id), ['approval_remark' => 'Wrong batch'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(StockOutRequest::STATUS_REJECTED, (int) $request->fresh()->status);
        $this->assertSame('Wrong batch', $request->fresh()->approval_remark);
        $this->assertSame(10, (int) $batch->fresh()->quantity);
        $this->assertSame(10, (int) $balance->fresh()->quantity);
    }

    public function test_non_admin_cannot_approve(): void
    {
        $batch   = $this->makeBatch(10);
        $wh      = $this->makeWarehouse();
        $this->seedBalance($wh, $batch, 10);
        $request = $this->makeRequest($wh, $batch, 6);

        $user = User::create([
            'name'     => 'SO NONADMIN ' . uniqid(),
            'email'    => 'so_nonadmin_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
        ]);
        $user->assignRole('Inventory Admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($user)
            ->post(route('stockOutRequests.approve', $request->id));

        $this->assertContains($response->getStatusCode(), [403, 302]);
        $this->assertSame(StockOutRequest::STATUS_PENDING, (int) $request->fresh()->status);
    }

    private function seedBalanceId(Warehouse $wh, ProductBatch $batch): int
    {
        return (int) WarehouseInventoryBalance::where('warehouse_id', $wh->id)
            ->where('batch_id', $batch->id)
            ->value('id');
    }
}
