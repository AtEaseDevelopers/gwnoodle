<?php

namespace Tests\Feature;

use App\Http\Controllers\AssignController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers AssignController::customerfindgroup(), which builds the customer list
 * shown when assigning a group's customers to a driver route. Inactive
 * customers (status != 1) must not appear in that selectable list.
 *
 * Runs against the shared dev database (DatabaseTransactions), so assertions
 * target the specific rows created here rather than the full result set.
 */
class AssignCustomerFindGroupTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCustomer(int $status, string $group): int
    {
        return DB::table('customers')->insertGetId([
            'code'    => 'CFG/' . uniqid(),
            'company' => 'CFG ' . uniqid(),
            'group'   => $group,
            'status'  => $status,
        ]);
    }

    private function findGroup(string $group, int $driverId): array
    {
        $request = Request::create('/customerfindgroup', 'POST', [
            'group_id'  => $group,
            'driver_id' => $driverId,
        ]);

        $response = app(AssignController::class)->customerfindgroup($request);

        return json_decode($response->getContent(), true);
    }

    public function test_active_customer_in_group_is_included(): void
    {
        $group = 'grp' . uniqid();
        $activeId = $this->makeCustomer(1, $group);

        $payload = $this->findGroup($group, 999999);

        $ids = array_column($payload['data'] ?? [], 'id');
        $this->assertContains($activeId, $ids);
    }

    public function test_inactive_customer_in_group_is_excluded(): void
    {
        $group = 'grp' . uniqid();
        $activeId = $this->makeCustomer(1, $group);
        $inactiveId = $this->makeCustomer(0, $group);

        $payload = $this->findGroup($group, 999999);

        $ids = array_column($payload['data'] ?? [], 'id');
        $this->assertContains($activeId, $ids);
        $this->assertNotContains($inactiveId, $ids);
    }
}
