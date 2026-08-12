<?php

namespace Tests\Feature;

use App\Models\ProductBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The Stock Card (stock transaction) report batch selector must list every
 * active batch of the chosen product so that historical transactions of
 * now-depleted batches can still be reported — regardless of whether the
 * batch currently has any remaining quantity.
 *
 * Runs against the shared dev database (DatabaseTransactions).
 */
class StockCardBatchDropdownAllBatchesTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(): User
    {
        $user = User::create([
            'name'     => 'SC BATCH ' . uniqid(),
            'email'    => 'scbatch_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
        ]);

        $user->givePermissionTo('report');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function makeProductId(): int
    {
        return DB::table('products')->insertGetId([
            'name'   => 'SC BATCH PROD ' . uniqid(),
            'price'  => 0,
            'cost'   => 0,
            'status' => 1,
        ]);
    }

    public function test_batch_dropdown_includes_zero_quantity_batches(): void
    {
        $productId = $this->makeProductId();

        $inStock = ProductBatch::create([
            'product_id' => $productId,
            'batch_code' => 'BATCH-INSTOCK-' . uniqid(),
            'expiry_date' => '2027-01-01',
            'quantity'   => 25,
            'status'     => ProductBatch::STATUS_ACTIVE,
        ]);

        $depleted = ProductBatch::create([
            'product_id' => $productId,
            'batch_code' => 'BATCH-DEPLETED-' . uniqid(),
            'expiry_date' => '2027-02-01',
            'quantity'   => 0,
            'status'     => ProductBatch::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->makeUser())
            ->getJson(route('reports.getProductBatches', ['product_id' => $productId]));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $ids = collect($response->json('batches'))->pluck('id')->all();

        $this->assertContains($inStock->id, $ids, 'In-stock batch should be listed');
        $this->assertContains($depleted->id, $ids, 'Depleted (zero-quantity) batch should still be listed');
    }
}
