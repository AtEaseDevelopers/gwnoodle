<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers Customer::activeSelectOptions(), the single source of truth for the
 * customer_id dropdown used by the invoice and assign forms. Inactive customers
 * (status != 1) must be excluded, except when they are the currently saved value
 * on the record being edited - otherwise the select would show nothing selected.
 *
 * Runs against the shared dev database (DatabaseTransactions), so assertions
 * target specific rows created here rather than the full option set.
 */
class CustomerActiveSelectOptionsTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCustomer(int $status, string $company): int
    {
        return DB::table('customers')->insertGetId([
            'code'    => 'SEL/' . uniqid(),
            'company' => $company,
            'status'  => $status,
        ]);
    }

    public function test_active_customer_is_included(): void
    {
        $id = $this->makeCustomer(1, 'ACTIVE ' . uniqid());

        $this->assertArrayHasKey($id, Customer::activeSelectOptions());
    }

    public function test_inactive_customer_is_excluded(): void
    {
        $id = $this->makeCustomer(0, 'INACTIVE ' . uniqid());

        $this->assertArrayNotHasKey($id, Customer::activeSelectOptions());
    }

    public function test_selected_inactive_customer_is_kept_and_marked(): void
    {
        $id = $this->makeCustomer(0, 'KEEP ' . uniqid());

        $options = Customer::activeSelectOptions($id);

        $this->assertArrayHasKey($id, $options);
        $this->assertStringContainsString('inactive', strtolower($options[$id]));
    }

    public function test_inactive_customer_not_added_when_a_different_row_is_selected(): void
    {
        $id = $this->makeCustomer(0, 'NOSEL ' . uniqid());
        $activeId = $this->makeCustomer(1, 'OTHER ' . uniqid());

        $options = Customer::activeSelectOptions($activeId);

        $this->assertArrayNotHasKey($id, $options);
    }

    public function test_null_selection_returns_active_only(): void
    {
        $id = $this->makeCustomer(0, 'NULLSEL ' . uniqid());

        $this->assertArrayNotHasKey($id, Customer::activeSelectOptions(null));
    }
}
