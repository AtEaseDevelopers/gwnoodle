<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the `assigns:auto-assign` command, which ensures every active customer
 * has an active assign row for every active driver (creating or reactivating any
 * missing pair). customers.driver_id is not used.
 *
 * Runs against the shared dev database (DatabaseTransactions), so assertions
 * target the specific rows created in each test rather than global counts.
 */
class AutoAssignCustomersTest extends TestCase
{
    use DatabaseTransactions;

    private function makeDriver(int $status = 1): int
    {
        $suffix = uniqid();

        return DB::table('drivers')->insertGetId([
            'employeeid'   => 'AA/' . $suffix,
            'password'     => 'x',
            'name'         => 'AA DRIVER ' . $suffix,
            'invoice_code' => 'AA' . substr($suffix, -4),
            'status'       => $status,
        ]);
    }

    private function makeCustomer(int $status, ?int $driverId): int
    {
        return DB::table('customers')->insertGetId([
            'code'      => 'AA/' . uniqid(),
            'company'   => 'AA CUSTOMER',
            'status'    => $status,
            'driver_id' => $driverId,
        ]);
    }

    private function makeAssign(int $driverId, int $customerId, int $status = 1, ?string $deletedAt = null): int
    {
        return DB::table('assigns')->insertGetId([
            'driver_id'   => $driverId,
            'customer_id' => $customerId,
            'sequence'    => 1,
            'status'      => $status,
            'deleted_at'  => $deletedAt,
        ]);
    }

    private function activeAssignCount(int $driverId, int $customerId): int
    {
        return DB::table('assigns')
            ->where('driver_id', $driverId)
            ->where('customer_id', $customerId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->count();
    }

    private function runCommand(): void
    {
        Artisan::call('assigns:auto-assign');
    }

    public function test_creates_active_assign_for_unassigned_active_customer(): void
    {
        $driverId = $this->makeDriver();
        $customerId = $this->makeCustomer(1, $driverId);

        $this->runCommand();

        $this->assertSame(1, $this->activeAssignCount($driverId, $customerId));
    }

    public function test_gives_new_assigns_distinct_incrementing_sequences_per_driver(): void
    {
        $driverId = $this->makeDriver();
        $custA = $this->makeCustomer(1, null);
        $custB = $this->makeCustomer(1, null);

        $this->runCommand();

        $seqA = (int) DB::table('assigns')
            ->where('driver_id', $driverId)->where('customer_id', $custA)->value('sequence');
        $seqB = (int) DB::table('assigns')
            ->where('driver_id', $driverId)->where('customer_id', $custB)->value('sequence');

        // Both get a real sequence and the per-run cache keeps them from colliding.
        $this->assertGreaterThan(0, $seqA);
        $this->assertGreaterThan(0, $seqB);
        $this->assertNotSame($seqA, $seqB);
    }

    public function test_does_not_duplicate_when_customer_already_actively_assigned(): void
    {
        $driverId = $this->makeDriver();
        $customerId = $this->makeCustomer(1, $driverId);
        $this->makeAssign($driverId, $customerId, 1);

        $this->runCommand();

        $this->assertSame(1, $this->activeAssignCount($driverId, $customerId));
    }

    public function test_reactivates_an_inactive_assign_instead_of_creating_new(): void
    {
        $driverId = $this->makeDriver();
        $customerId = $this->makeCustomer(1, $driverId);
        $assignId = $this->makeAssign($driverId, $customerId, 0); // inactive

        $this->runCommand();

        $this->assertSame(1, $this->activeAssignCount($driverId, $customerId));
        $this->assertSame(1, (int) DB::table('assigns')->where('id', $assignId)->value('status'));
    }

    public function test_skips_inactive_customer(): void
    {
        $driverId = $this->makeDriver();
        $customerId = $this->makeCustomer(0, $driverId); // inactive customer

        $this->runCommand();

        $this->assertSame(0, $this->activeAssignCount($driverId, $customerId));
    }

    public function test_assigns_customer_even_without_driver_id(): void
    {
        // driver_id on the customer is irrelevant now - it still gets assigned
        // to every active driver.
        $driverId = $this->makeDriver();
        $customerId = $this->makeCustomer(1, null);

        $this->runCommand();

        $this->assertSame(1, $this->activeAssignCount($driverId, $customerId));
    }

    public function test_assigns_active_customer_to_all_active_drivers(): void
    {
        $driverA = $this->makeDriver();
        $driverB = $this->makeDriver();
        $customerId = $this->makeCustomer(1, null);

        $this->runCommand();

        $this->assertSame(1, $this->activeAssignCount($driverA, $customerId));
        $this->assertSame(1, $this->activeAssignCount($driverB, $customerId));
    }

    public function test_does_not_assign_to_a_deleted_driver(): void
    {
        $deletedDriverId = $this->makeDriver(3); // STATUS_DELETED
        $customerId = $this->makeCustomer(1, null);

        $this->runCommand();

        $this->assertSame(0, $this->activeAssignCount($deletedDriverId, $customerId));
    }
}
