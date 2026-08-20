<?php

namespace App\Console\Commands;

use App\Models\Assign;
use App\Models\Customer;
use App\Models\Driver;
use Illuminate\Console\Command;

/**
 * Daily job that makes sure every active customer has an active assign row for
 * every active driver. For each active customer x active driver pair that has
 * no active assign, one is created (or a previously deactivated/soft-deleted
 * row for that pair is reactivated instead of duplicating).
 *
 * "Active driver" = drivers.status != STATUS_DELETED. customers.driver_id is
 * NOT used here - customers are assigned to all drivers, not a designated one.
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
    protected $description = 'Ensure every active customer has an active assign for every active driver (creates/reactivates any missing pair)';

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

        // Cache the next sequence per driver so multiple new assigns in the same
        // run don't collide on the same sequence number.
        $nextSequence = [];

        // Every active customer should be assigned to every active driver.
        $driverIds = Driver::where('status', '!=', Driver::STATUS_DELETED)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if (empty($driverIds)) {
            $this->warn('No active drivers found; nothing to assign.');
            return 0;
        }

        Customer::query()
            ->where('status', 1)
            ->orderBy('id')
            ->chunkById(500, function ($customers) use (
                &$created, &$reactivated, &$nextSequence, $dryRun, $driverIds
            ) {
                foreach ($customers as $customer) {
                    foreach ($driverIds as $driverId) {
                        $driverId = (int) $driverId;

                        // Reuse an existing assign row for this driver+customer if
                        // one exists (e.g. previously deactivated/soft-deleted)
                        // instead of creating a duplicate.
                        $existing = Assign::where('driver_id', $driverId)
                            ->where('customer_id', $customer->id)
                            ->orderBy('id')
                            ->first();

                        if ($existing) {
                            // Already active and not soft-deleted - nothing to do.
                            if ((int) $existing->status === Assign::STATUS_ACTIVE
                                && empty($existing->deleted_at)) {
                                continue;
                            }

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
                }
            });

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}Auto-assign done. Created: {$created}, Reactivated: {$reactivated}.");

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
