<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the `assigns:auto-assign` command, which finds active customers with
 * no active assign row and assigns them to their designated driver
 * (customers.driver_id).
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

    public function test_gives_new_assign_next_sequence_for_the_driver(): void
    {
        $driverId = $this->makeDriver();
        $other = $this->makeCustomer(1, $driverId);
        $this->makeAssign($driverId, $other, 1);            // existing seq 1, active
        DB::table('assigns')->where('driver_id', $driverId)->where('customer_id', $other)->update(['sequence' => 5]);

        $customerId = $this->makeCustomer(1, $driverId);
        $this->runCommand();

        $sequence = DB::table('assigns')
            ->where('driver_id', $driverId)->where('customer_id', $customerId)
            ->value('sequence');

        $this->assertSame(6, (int) $sequence);
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

    public function test_skips_customer_without_driver_id(): void
    {
        $customerId = $this->makeCustomer(1, null);

        $this->runCommand();

        $this->assertSame(
            0,
            DB::table('assigns')->where('customer_id', $customerId)->count()
        );
    }

    public function test_skips_customer_whose_driver_is_deleted(): void
    {
        $driverId = $this->makeDriver(3); // STATUS_DELETED
        $customerId = $this->makeCustomer(1, $driverId);

        $this->runCommand();

        $this->assertSame(0, $this->activeAssignCount($driverId, $customerId));
    }
}
