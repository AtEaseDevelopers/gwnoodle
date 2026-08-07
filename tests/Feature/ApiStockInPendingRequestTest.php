<?php

namespace Tests\Feature;

use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\StockInRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The mobile stock-in endpoint (POST /api/v1/driver/stock-in-product-batch)
 * must queue an approval request rather than applying stock directly, mirroring
 * the web bulk stock-in flow. Nothing should touch the batch quantity, warehouse
 * balance or inventory ledger until an admin approves the StockInRequest.
 *
 * Runs against the shared dev database (DatabaseTransactions), so fixtures are
 * created per-test and assertions target those specific rows.
 */
class ApiStockInPendingRequestTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUserWithSession(): User
    {
        return User::create([
            'name'     => 'API STOCKIN ' . uniqid(),
            'email'    => 'apistockin_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
            'session'  => 'sess_' . uniqid(),
        ]);
    }

    private function makeBatch(int $startingQty = 0): ProductBatch
    {
        $productId = DB::table('products')->insertGetId([
            'name'   => 'API STOCKIN PROD ' . uniqid(),
            'price'  => 0,
            'cost'   => 0,
            'status' => 1,
        ]);

        return ProductBatch::create([
            'product_id' => $productId,
            'batch_code' => 'APISI/' . uniqid(),
            'quantity'   => $startingQty,
            'status'     => 1,
        ]);
    }

    private function makeWarehouse(): Warehouse
    {
        return Warehouse::create([
            'name'   => 'API STOCKIN WH ' . uniqid(),
            'status' => 1,
        ]);
    }

    public function test_api_stock_in_queues_pending_request_without_applying_stock(): void
    {
        $user      = $this->makeUserWithSession();
        $batch     = $this->makeBatch(10);
        $warehouse = $this->makeWarehouse();

        $response = $this->withHeader('session', $user->session)
            ->postJson('/api/v1/driver/stock-in-product-batch', [
                'batch_code'   => $batch->batch_code,
                'quantity'     => 7,
                'warehouse_id' => $warehouse->id,
                'remark'       => 'Scanned at dock',
            ]);

        $response->assertOk()->assertJson(['result' => true]);
        $this->assertStringContainsString('submitted for approval', $response->json('message'));

        // A pending request was created carrying the requested quantity.
        $request = StockInRequest::where('batch_id', $batch->id)
            ->where('requested_by', $user->name)
            ->first();

        $this->assertNotNull($request);
        $this->assertSame(StockInRequest::STATUS_PENDING, (int) $request->status);
        $this->assertSame(7, (int) $request->requested_quantity);
        $this->assertSame(7, (int) $request->quantity);
        $this->assertSame($warehouse->id, (int) $request->warehouse_id);
        $this->assertSame('Scanned at dock', $request->remark);

        // Nothing is applied yet: batch quantity, warehouse balance and ledger untouched.
        $this->assertSame(10, (int) $batch->fresh()->quantity);
        $this->assertDatabaseMissing('warehouse_inventory_balances', [
            'warehouse_id' => $warehouse->id,
            'batch_id'     => $batch->id,
        ]);
        $this->assertFalse(
            InventoryTransaction::where('batch_id', $batch->id)
                ->where('type', InventoryTransaction::TYPE_STOCK_IN)
                ->exists()
        );
    }

    public function test_api_stock_in_rejects_invalid_session(): void
    {
        $batch     = $this->makeBatch(0);
        $warehouse = $this->makeWarehouse();

        $this->withHeader('session', 'not-a-real-session')
            ->postJson('/api/v1/driver/stock-in-product-batch', [
                'batch_code'   => $batch->batch_code,
                'quantity'     => 5,
                'warehouse_id' => $warehouse->id,
            ])
            ->assertStatus(401);

        $this->assertFalse(StockInRequest::where('batch_id', $batch->id)->exists());
    }
}
