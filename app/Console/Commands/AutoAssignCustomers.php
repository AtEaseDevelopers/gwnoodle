<?php

namespace App\Console\Commands;

use App\Models\Assign;
use App\Models\Customer;
use App\Models\Driver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Daily job that finds active customers who are not currently assigned to any
 * driver and auto-assigns them to their designated driver (customers.driver_id).
 *
 * "Not assigned" means the customer has no active assign row (status = 1 and
 * not soft-deleted). Customers without a driver_id, or whose driver_id points
 * to a missing/deleted driver, are skipped (they can't be inferred).
 *
 * The Assign model has SoftDeletes disabled, so deleted_at is filtered manually.
 */
class AutoAssignCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assigns:auto-assign {--dry-run : Preview what would be assigned without writing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-assign active customers with no active assign to their designated driver (customers.driver_id)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        $created = 0;
        $reactivated = 0;
        $skipped = 0;

        // Cache the next sequence per driver so multiple new assigns in the same
        // run don't collide on the same sequence number.
        $nextSequence = [];

        Customer::query()
            ->where('status', 1)
            ->whereNotNull('driver_id')
            ->where('driver_id', '>', 0)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('assigns')
                    ->whereColumn('assigns.customer_id', 'customers.id')
                    ->where('assigns.status', Assign::STATUS_ACTIVE)
                    ->whereNull('assigns.deleted_at');
            })
            ->orderBy('id')
            ->chunkById(500, function ($customers) use (
                &$created, &$reactivated, &$skipped, &$nextSequence, $dryRun
            ) {
                foreach ($customers as $customer) {
                    $driverId = (int) $customer->driver_id;

                    $driver = Driver::find($driverId);
                    if (! $driver || (string) $driver->status === Driver::STATUS_DELETED) {
                        $this->warn("Skip customer #{$customer->id} ({$customer->code}): driver #{$driverId} missing or deleted.");
                        $skipped++;
                        continue;
                    }

                    // Reuse an existing assign row for this driver+customer if one
                    // exists (e.g. previously deactivated/soft-deleted) instead of
                    // creating a duplicate.
                    $existing = Assign::where('driver_id', $driverId)
                        ->where('customer_id', $customer->id)
                        ->orderBy('id')
                        ->first();

                    if ($existing) {
                        if ($dryRun) {
                            $this->line("[dry-run] Reactivate assign #{$existing->id}: driver #{$driverId} -> customer #{$customer->id} ({$customer->code})");
                            $reactivated++;
                            continue;
                        }

                        $existing->status = Assign::STATUS_ACTIVE;
                        $existing->deleted_at = null;
                        if (empty($existing->sequence)) {
                            $existing->sequence = $this->pullNextSequence($driverId, $nextSequence);
                        }
                        $existing->save();
                        $reactivated++;
                        continue;
                    }

                    $sequence = $this->pullNextSequence($driverId, $nextSequence);

                    if ($dryRun) {
                        $this->line("[dry-run] Create assign: driver #{$driverId} -> customer #{$customer->id} ({$customer->code}) seq {$sequence}");
                        $created++;
                        continue;
                    }

                    $assign = new Assign();
                    $assign->driver_id = $driverId;
                    $assign->customer_id = $customer->id;
                    $assign->sequence = $sequence;
                    $assign->status = Assign::STATUS_ACTIVE;
                    $assign->save();
                    $created++;
                }
            });

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}Auto-assign done. Created: {$created}, Reactivated: {$reactivated}, Skipped: {$skipped}.");

        return 0;
    }

    /**
     * Get the next sequence number for a driver, incrementing a per-run cache so
     * multiple assigns created in the same run stay ordered and don't collide.
     */
    private function pullNextSequence(int $driverId, array &$cache): int
    {
        if (! array_key_exists($driverId, $cache)) {
            $cache[$driverId] = (int) Assign::where('driver_id', $driverId)
                ->whereNull('deleted_at')
                ->max('sequence');
        }

        return ++$cache[$driverId];
    }
}
