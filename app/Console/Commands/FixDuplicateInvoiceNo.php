<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InventoryTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDuplicateInvoiceNo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:fix-duplicate-invoiceno
        {--invoiceno= : Only fix this specific duplicated invoiceno (default: scan all)}
        {--apply : Actually perform the update (default is dry-run preview only)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find invoices that share the same invoiceno and reassign a fresh, non-colliding running number to the duplicate(s) - keeping the AutoCount-synced original, renumbering the failed one and re-queueing it for AutoCount sync. Also rewrites matching inventory_transactions.remark strings.';

    public function handle()
    {
        $apply = (bool) $this->option('apply');
        $only  = $this->option('invoiceno');

        // Find every invoiceno that is used by more than one invoice.
        $dupeQuery = Invoice::select('invoiceno')
            ->groupBy('invoiceno')
            ->havingRaw('COUNT(*) > 1');

        if ($only) {
            $dupeQuery->where('invoiceno', $only);
        }

        $dupeNumbers = $dupeQuery->pluck('invoiceno');

        if ($dupeNumbers->isEmpty()) {
            $this->info($only
                ? "No duplicates found for invoiceno {$only}."
                : 'No duplicate invoicenos found. Nothing to do.');
            return 0;
        }

        // Numbers already taken across the whole table - a renumber must avoid
        // every one of these, plus the new numbers we allocate during this run.
        $takenNumbers = Invoice::pluck('invoiceno')->flip();

        $plan = [];

        foreach ($dupeNumbers as $invoiceno) {
            $group = Invoice::with('customer:id,company')
                ->where('invoiceno', $invoiceno)
                ->orderBy('id')
                ->get(['id', 'invoiceno', 'date', 'customer_id', 'driver_id', 'autocount_status']);

            // Renumber the record(s) AutoCount rejected (status "failed") - those
            // are the ones the accounting system never booked, so they're safe to
            // reassign. The non-failed row (success/pending) keeps the number.
            $toRenumber = $group->where('autocount_status', 'failed');

            if ($toRenumber->isEmpty()) {
                $this->warn("Skipped {$invoiceno}: no record with autocount_status 'failed' to renumber.");
                continue;
            }

            foreach ($toRenumber as $invoice) {
                $newNo = $this->nextAvailableNumber($invoice->invoiceno, $takenNumbers);
                $takenNumbers->put($newNo, true);

                $plan[] = [
                    'id'       => $invoice->id,
                    'old'      => $invoice->invoiceno,
                    'new'      => $newNo,
                    'date'     => $invoice->date,
                    'customer' => optional($invoice->customer)->company ?? '-',
                    'status'   => $invoice->autocount_status,
                ];
            }
        }

        if (empty($plan)) {
            $this->info('Every duplicate group already has a clear keeper and nothing left to renumber.');
            return 0;
        }

        $this->info('Duplicate invoicenos found: ' . $dupeNumbers->count());
        $this->info('Invoices to renumber: ' . count($plan));

        $this->table(
            ['Invoice ID', 'Old #', 'New #', 'Date', 'Customer', 'AutoCount'],
            array_map(fn($p) => [$p['id'], $p['old'], $p['new'], $p['date'], $p['customer'], $p['status']], $plan)
        );

        if (!$apply) {
            $this->comment('Dry run only. Re-run with --apply to commit these changes.');
            return 0;
        }

        if (!$this->confirm('This will renumber ' . count($plan) . ' invoice(s), rewrite matching inventory_transactions.remark strings, and re-queue them for AutoCount. Continue?')) {
            $this->comment('Aborted.');
            return 0;
        }

        DB::beginTransaction();
        try {
            // Phase 1: move every affected invoice to a unique temp placeholder so
            // old/new numbers can never collide with each other mid-batch.
            foreach ($plan as $row) {
                Invoice::where('id', $row['id'])->update(['invoiceno' => 'TMP-DUP-' . $row['id']]);
            }

            // Phase 2: apply the final number, rewrite inventory_transactions
            // remarks that reference the old invoiceno, and re-queue for AutoCount
            // (the plugin polls invoices where autocount_status = 'pending').
            foreach ($plan as $row) {
                Invoice::where('id', $row['id'])->update([
                    'invoiceno'         => $row['new'],
                    'autocount_status'  => 'pending',
                    'autocount_message' => null,
                ]);

                $likeSafeOld = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $row['old']);
                InventoryTransaction::where('remark', 'like', '%' . $likeSafeOld . '%')
                    ->get(['id', 'remark'])
                    ->each(function ($tx) use ($row) {
                        $tx->remark = str_replace($row['old'], $row['new'], $tx->remark);
                        $tx->save();
                    });
            }

            DB::commit();
            $this->info('Renumbered ' . count($plan) . ' invoice(s) and re-queued them for AutoCount successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed, rolled back: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Compute the next free running number that shares the given invoiceno's
     * prefix (everything up to and including the last "/"). Skips offline "A"
     * numbers and anything already present in $taken (existing rows + numbers
     * allocated earlier in this run).
     */
    private function nextAvailableNumber(string $invoiceno, $taken): string
    {
        $slash  = strrpos($invoiceno, '/');
        $prefix = $slash === false ? $invoiceno : substr($invoiceno, 0, $slash + 1);

        // Highest existing numeric (non-offline) suffix under this prefix.
        $maxNumber = Invoice::where('invoiceno', 'like', $prefix . '%')
            ->where('invoiceno', 'not like', $prefix . 'A%')
            ->get(['invoiceno'])
            ->map(fn($i) => (int) substr($i->invoiceno, strlen($prefix)))
            ->max() ?? 0;

        $next = $maxNumber + 1;

        do {
            $candidate = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while ($taken->has($candidate));

        return $candidate;
    }
}
