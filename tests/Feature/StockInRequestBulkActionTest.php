<?php

namespace Tests\Feature;

use App\Models\ProductBatch;
use App\Models\StockInRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Covers the bulk approve/reject endpoints on StockInRequestController. Bulk
 * approve must apply the stock (increment the batch, write a ledger row) and
 * mark each request approved; bulk reject must set the shared remark. Both must
 * silently skip requests that are no longer pending, and stay admin-only.
 *
 * Runs against the shared dev database (DatabaseTransactions), so fixtures are
 * created per-test and assertions target those specific rows.
 */
class StockInRequestBulkActionTest extends TestCase
{
    use DatabaseTransactions;

    private function makeAdmin(): User
    {
        $user = User::create([
            'name'     => 'BULK ADMIN ' . uniqid(),
            'email'    => 'bulk_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole('admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function makeBatch(int $startingQty = 0): ProductBatch
    {
        $productId = DB::table('products')->insertGetId([
            'name'   => 'BULK PROD ' . uniqid(),
            'price'  => 0,
            'cost'   => 0,
            'status' => 1,
        ]);

        return ProductBatch::create([
            'product_id' => $productId,
            'batch_code' => 'BULK/' . uniqid(),
            'quantity'   => $startingQty,
            'status'     => 1,
        ]);
    }

    private function makeRequest(ProductBatch $batch, int $qty, int $status = StockInRequest::STATUS_PENDING): StockInRequest
    {
        return StockInRequest::create([
            'source'             => StockInRequest::SOURCE_BULK_SCAN,
            'product_id'         => $batch->product_id,
            'batch_id'           => $batch->id,
            'requested_quantity' => $qty,
            'quantity'           => $qty,
            'status'             => $status,
        ]);
    }

    public function test_bulk_approve_applies_stock_and_marks_approved(): void
    {
        $batch = $this->makeBatch(10);
        $a = $this->makeRequest($batch, 5);
        $b = $this->makeRequest($batch, 3);

        $this->actingAs($this->makeAdmin())
            ->post(route('stockInRequests.bulk-approve'), ['ids' => [$a->id, $b->id]])
            ->assertOk()
            ->assertJson(['success' => true, 'approved' => 2]);

        $this->assertSame(StockInRequest::STATUS_APPROVED, (int) $a->fresh()->status);
        $this->assertSame(StockInRequest::STATUS_APPROVED, (int) $b->fresh()->status);
        // 10 starting + 5 + 3 applied
        $this->assertSame(18, (int) $batch->fresh()->quantity);
    }

    public function test_bulk_approve_skips_already_reviewed_requests(): void
    {
        $batch = $this->makeBatch(0);
        $pending = $this->makeRequest($batch, 4);
        $approved = $this->makeRequest($batch, 7, StockInRequest::STATUS_APPROVED);

        $this->actingAs($this->makeAdmin())
            ->post(route('stockInRequests.bulk-approve'), ['ids' => [$pending->id, $approved->id]])
            ->assertOk()
            ->assertJson(['success' => true, 'approved' => 1, 'skipped' => 1]);

        // Only the pending request's quantity is applied to the batch.
        $this->assertSame(4, (int) $batch->fresh()->quantity);
    }

    public function test_bulk_reject_sets_remark_and_status(): void
    {
        $batch = $this->makeBatch(0);
        $a = $this->makeRequest($batch, 5);
        $b = $this->makeRequest($batch, 6);

        $this->actingAs($this->makeAdmin())
            ->post(route('stockInRequests.bulk-reject'), [
                'ids'             => [$a->id, $b->id],
                'approval_remark' => 'Damaged on arrival',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'rejected' => 2]);

        foreach ([$a, $b] as $req) {
            $fresh = $req->fresh();
            $this->assertSame(StockInRequest::STATUS_REJECTED, (int) $fresh->status);
            $this->assertSame('Damaged on arrival', $fresh->approval_remark);
        }

        // Rejection applies no stock.
        $this->assertSame(0, (int) $batch->fresh()->quantity);
    }

    public function test_bulk_reject_requires_a_remark(): void
    {
        $batch = $this->makeBatch(0);
        $req = $this->makeRequest($batch, 5);

        $this->actingAs($this->makeAdmin())
            ->post(route('stockInRequests.bulk-reject'), ['ids' => [$req->id]])
            ->assertStatus(422);

        $this->assertSame(StockInRequest::STATUS_PENDING, (int) $req->fresh()->status);
    }

    public function test_bulk_approve_rejects_empty_selection(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('stockInRequests.bulk-approve'), ['ids' => []])
            ->assertStatus(422);
    }

    public function test_non_admin_cannot_bulk_approve(): void
    {
        $batch = $this->makeBatch(0);
        $req = $this->makeRequest($batch, 5);

        $user = User::create([
            'name'     => 'BULK NONADMIN ' . uniqid(),
            'email'    => 'nonadmin_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
        ]);
        $user->assignRole('Inventory Admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Inventory Admin lacks the warehouse permission guarding these routes,
        // so the request is blocked before reaching the controller.
        $response = $this->actingAs($user)
            ->post(route('stockInRequests.bulk-approve'), ['ids' => [$req->id]]);

        $this->assertContains($response->getStatusCode(), [403, 302]);
        $this->assertSame(StockInRequest::STATUS_PENDING, (int) $req->fresh()->status);
    }
}
