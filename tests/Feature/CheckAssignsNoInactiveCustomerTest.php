<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the `assigns:check-inactive-customer` command, which flags any live
 * assign pointing to an inactive (status != 1) or missing customer.
 *
 * These tests run against the shared dev database (DatabaseTransactions), so
 * they assert on whether a *specific* assign row is reported rather than on the
 * global exit code, which depends on pre-existing data.
 */
class CheckAssignsNoInactiveCustomerTest extends TestCase
{
    use DatabaseTransactions;

    private string $customerCode;

    private function makeCustomer(int $status): int
    {
        $this->customerCode = 'CHK/' . uniqid();

        return DB::table('customers')->insertGetId([
            'code'    => $this->customerCode,
            'company' => 'CHK CUSTOMER',
            'status'  => $status,
        ]);
    }

    private function makeAssign(int $customerId, ?string $deletedAt = null): int
    {
        return DB::table('assigns')->insertGetId([
            'driver_id'   => 999999,
            'customer_id' => $customerId,
            'sequence'    => 1,
            'deleted_at'  => $deletedAt,
        ]);
    }

    private function runCommand(): string
    {
        Artisan::call('assigns:check-inactive-customer');

        return Artisan::output();
    }

    public function test_lists_an_assign_with_inactive_customer(): void
    {
        $assignId = $this->makeAssign($this->makeCustomer(0));

        $this->assertStringContainsString((string) $assignId, $this->runCommand());
    }

    public function test_does_not_list_an_assign_with_active_customer(): void
    {
        $this->makeAssign($this->makeCustomer(1));

        $this->assertStringNotContainsString($this->customerCode, $this->runCommand());
    }

    public function test_ignores_soft_deleted_assigns(): void
    {
        $this->makeAssign($this->makeCustomer(0), now()->toDateTimeString());

        $this->assertStringNotContainsString($this->customerCode, $this->runCommand());
    }
}
