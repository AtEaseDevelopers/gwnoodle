<?php

namespace Tests\Feature;

use App\Models\ProductBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The mobile create-batch endpoint must reject an expiry date that disagrees
 * with the date encoded in the batch code (group+user+YY+8+DD+MM+productCode).
 * This is the guard for the "2006 vs 2026" data-entry slip: the barcode said
 * 2026 while the submitted expiry said 2006 and nothing caught the mismatch.
 *
 * Runs against the shared dev database (DatabaseTransactions).
 */
class ApiProductBatchExpiryGuardTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUserWithSession(): User
    {
        return User::create([
            'name'     => 'API BATCHGUARD ' . uniqid(),
            'email'    => 'apibatchguard_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
            'session'  => 'sess_' . uniqid(),
        ]);
    }

    private function makeProductId(): int
    {
        return DB::table('products')->insertGetId([
            'name'   => 'API BATCHGUARD PROD ' . uniqid(),
            'price'  => 0,
            'cost'   => 0,
            'status' => 1,
        ]);
    }

    public function test_rejects_expiry_year_that_does_not_match_batch_code(): void
    {
        $user = $this->makeUserWithSession();
        $productId = $this->makeProductId();

        // First 10 chars "A002682409" encode 2026-09-24 (YY=26,fixed=8,DD=24,MM=09);
        // the expiry is submitted as 2006-09-24 -> must be rejected.
        $response = $this->withHeader('session', $user->session)
            ->postJson('/api/v1/driver/create-product-batch', [
                'product_id'  => $productId,
                'batch_code'  => 'A002682409N008-' . strtoupper(substr(uniqid(), -6)),
                'expiry_date' => '2006-09-24',
            ]);

        $response->assertOk()->assertJson(['result' => false]);
        $this->assertStringContainsString('does not match the batch code date', $response->json('message'));
        $this->assertDatabaseMissing('product_batches', ['expiry_date' => '2006-09-24', 'product_id' => $productId]);
    }

    public function test_allows_expiry_that_matches_batch_code(): void
    {
        $user = $this->makeUserWithSession();
        $productId = $this->makeProductId();
        $code = 'A002682409N008-' . strtoupper(substr(uniqid(), -6));

        $response = $this->withHeader('session', $user->session)
            ->postJson('/api/v1/driver/create-product-batch', [
                'product_id'  => $productId,
                'batch_code'  => $code,
                'expiry_date' => '2026-09-24', // matches the code
            ]);

        $response->assertOk()->assertJson(['result' => true]);
        $this->assertDatabaseHas('product_batches', ['batch_code' => $code, 'product_id' => $productId]);
    }
}
