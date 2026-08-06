<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Inventory managers (the "Inventory Admin" role, managed under /Managerusers)
 * may VIEW Vans (lorries) and Trips but must never create/edit/delete there.
 *
 * View access is granted by role name through the role_or_permission middleware,
 * while every write route stays gated by the plain `lorry`/`trip` permission that
 * managers do not hold. Runs against the shared dev database (DatabaseTransactions).
 */
class InventoryManagerVanTripAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function makeInventoryManager(): User
    {
        $user = User::create([
            'name'     => 'INV MGR ' . uniqid(),
            'email'    => 'invmgr_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole('Inventory Admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function makeFullAccessUser(): User
    {
        // A full-access user is identified by holding the raw module permissions.
        $user = User::create([
            'name'     => 'VAN MGR ' . uniqid(),
            'email'    => 'vanmgr_' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
        ]);

        $user->givePermissionTo('lorry');
        $user->givePermissionTo('trip');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_inventory_manager_can_view_vans_list(): void
    {
        $this->actingAs($this->makeInventoryManager())
            ->get(route('lorries.index'))
            ->assertOk();
    }

    public function test_inventory_manager_can_view_trips_list(): void
    {
        $this->actingAs($this->makeInventoryManager())
            ->get(route('trips.index'))
            ->assertOk();
    }

    public function test_inventory_manager_cannot_open_van_create(): void
    {
        $this->actingAs($this->makeInventoryManager())
            ->get(route('lorries.create'))
            ->assertForbidden();
    }

    public function test_inventory_manager_cannot_open_trip_create(): void
    {
        $this->actingAs($this->makeInventoryManager())
            ->get(route('trips.create'))
            ->assertForbidden();
    }

    public function test_inventory_manager_van_list_hides_create_link(): void
    {
        $this->actingAs($this->makeInventoryManager())
            ->get(route('lorries.index'))
            ->assertDontSee(route('lorries.create'));
    }

    public function test_full_access_user_can_open_van_create(): void
    {
        $this->actingAs($this->makeFullAccessUser())
            ->get(route('lorries.create'))
            ->assertOk();
    }

    public function test_full_access_user_van_list_shows_create_link(): void
    {
        $this->actingAs($this->makeFullAccessUser())
            ->get(route('lorries.index'))
            ->assertSee(route('lorries.create'));
    }
}
