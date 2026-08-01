<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InventoryTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RenumberDriverInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:renumber {--apply : Actually perform the update (default is dry-run preview only)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompute invoiceno for ALL invoices (driver-created and admin-created) using the "I{yy}{mm}/{code}/{seq}" format, in creation order, and rewrite matching inventory_transactions.remark strings';

    /**
     * Admin-side invoiceno codes that are known to be stale (the account's
     * invoice_code changed since). Only two admin accounts exist (ADMIN,
     * currently code "0", and "Inventory Admin1", currently "INVENTORY1")
     * and "INVENTORY1"-coded invoices already match that account's current
     * code, so by elimination any invoice embedding the old "admin001" code
     * must belong to the ADMIN account and should be remapped to "0".
     */
    private const ADMIN_CODE_REMAP = [
        'admin001' => '0',
    ];

    /**
     * Fallback code for admin invoices whose invoiceno doesn't even match the
     * "PREFIX{yymm}/{code}/{num}" pattern (legacy plain running-number
     * format, e.g. "INV0000037"). These predate the code/date format and
     * were created around the same time as the earliest "admin001" invoices,
     * so they're bucketed into the same (now "0") group.
     */
    private const LEGACY_FALLBACK_CODE = '0';

    public function handle()
    {
        $apply = (bool) $this->option('apply');

        $plan = [];
        $skippedNoCode = [];

        $this->buildDriverPlan($plan, $skippedNoCode);
        $this->buildAdminPlan($plan);

        $totalScanned = Invoice::whereNotNull('trip_uuid')->whereNotNull('driver_id')->count()
            + Invoice::whereNull('trip_uuid')->count();

        $this->info('Total invoices scanned: ' . $totalScanned);
        $this->info('Invoices needing renumber: ' . count($plan));
        $this->info('Invoices skipped (driver has no invoice_code): ' . count($skippedNoCode));

        if (!empty($skippedNoCode)) {
            $this->warn('Skipped invoice IDs: ' . collect($skippedNoCode)->pluck('id')->implode(', '));
        }

        // Edge case: trip_uuid set but driver_id somehow null falls into neither
        // bucket above and would otherwise be silently left untouched.
        $orphans = Invoice::whereNotNull('trip_uuid')->whereNull('driver_id')->get(['id', 'invoiceno']);
        if ($orphans->isNotEmpty()) {
            $this->warn('WARNING: found ' . $orphans->count() . ' invoice(s) with trip_uuid set but no driver_id - not covered by this command, left untouched:');
            foreach ($orphans as $o) {
                $this->warn("  Invoice #{$o->id}: {$o->invoiceno}");
            }
        }

        if (empty($plan)) {
            $this->info('Nothing to do.');
            return 0;
        }

        $preview = array_map(fn($p) => [$p['id'], $p['old'], $p['new']], array_slice($plan, 0, 50));
        $this->table(['Invoice ID', 'Old #', 'New #'], $preview);
        if (count($plan) > 50) {
            $this->line('... and ' . (count($plan) - 50) . ' more not shown in preview');
        }

        // Safety check: make sure none of the computed new numbers already belong to
        // some other invoice that isn't part of this batch (would violate the unique
        // constraint on invoiceno even with the two-phase temp-rename below).
        $planIds = array_column($plan, 'id');
        $newNumbers = array_column($plan, 'new');
        $conflicts = Invoice::whereIn('invoiceno', $newNumbers)
            ->whereNotIn('id', $planIds)
            ->get(['id', 'invoiceno']);
        if ($conflicts->isNotEmpty()) {
            $this->error('Aborting: the following existing invoices already use a number this plan would assign elsewhere:');
            foreach ($conflicts as $c) {
                $this->error("  Invoice #{$c->id} already has invoiceno {$c->invoiceno}");
            }
            return 1;
        }

        if (!$apply) {
            $this->comment('Dry run only. Re-run with --apply to commit these changes.');
            return 0;
        }

        if (!$this->confirm('This will rewrite ' . count($plan) . ' invoice number(s) and matching inventory_transactions.remark strings. Continue?')) {
            $this->comment('Aborted.');
            return 0;
        }

        DB::beginTransaction();
        try {
            // Phase 1: move every affected invoice to a unique temp placeholder so
            // old/new numbers can never collide with each other mid-batch.
            foreach ($plan as $row) {
                Invoice::where('id', $row['id'])->update(['invoiceno' => 'TMP-RENUMBER-' . $row['id']]);
            }

            // Phase 2: apply the final number and rewrite any inventory_transactions
            // remark that references the old invoiceno.
            $bar = $this->output->createProgressBar(count($plan));
            $bar->start();
            foreach ($plan as $row) {
                Invoice::where('id', $row['id'])->update(['invoiceno' => $row['new']]);

                $likeSafeOld = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $row['old']);
                InventoryTransaction::where('remark', 'like', '%' . $likeSafeOld . '%')
                    ->get(['id', 'remark'])
                    ->each(function ($tx) use ($row) {
                        $tx->remark = str_replace($row['old'], $row['new'], $tx->remark);
                        $tx->save();
                    });

                $bar->advance();
            }
            $bar->finish();
            $this->newLine();

            DB::commit();
            $this->info('Renumbered ' . count($plan) . ' invoice(s) successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed, rolled back: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Driver-created invoices (trip_uuid set): bucket by driver + creation
     * year-month, code = the driver's CURRENT invoice_code.
     */
    private function buildDriverPlan(array &$plan, array &$skippedNoCode): void
    {
        $invoices = Invoice::whereNotNull('trip_uuid')
            ->whereNotNull('driver_id')
            ->with('driver:id,invoice_code')
            ->orderBy('id')
            ->get(['id', 'invoiceno', 'driver_id', 'created_at']);

        $groups = $invoices->groupBy(function (Invoice $invoice) {
            return $invoice->driver_id . '|' . $invoice->created_at->format('ym');
        });

        foreach ($groups as $group) {
            $driver = $group->first()->driver;
            $code = $driver->invoice_code ?? null;
            $ym = $group->first()->created_at->format('ym');

            if (empty($code)) {
                foreach ($group as $invoice) {
                    $skippedNoCode[] = $invoice;
                }
                continue;
            }

            $this->assignSequential($plan, $group, "I{$ym}/{$code}/");
        }
    }

    /**
     * Admin-created invoices (trip_uuid null): bucket by the code embedded in
     * the OLD invoiceno (remapped via ADMIN_CODE_REMAP where known to be
     * stale, or LEGACY_FALLBACK_CODE where no code is present at all) +
     * creation year-month.
     */
    private function buildAdminPlan(array &$plan): void
    {
        $invoices = Invoice::whereNull('trip_uuid')
            ->orderBy('id')
            ->get(['id', 'invoiceno', 'created_at']);

        $groups = $invoices->groupBy(function (Invoice $invoice) {
            $code = $this->resolveAdminCode($invoice->invoiceno);
            return $code . '|' . $invoice->created_at->format('ym');
        });

        foreach ($groups as $group) {
            $code = $this->resolveAdminCode($group->first()->invoiceno);
            $ym = $group->first()->created_at->format('ym');

            $this->assignSequential($plan, $group, "I{$ym}/{$code}/");
        }
    }

    private function resolveAdminCode(string $invoiceno): string
    {
        if (preg_match('#^I(?:NV)?\d{4}/(.+)/\d+$#', $invoiceno, $m)) {
            return self::ADMIN_CODE_REMAP[$m[1]] ?? $m[1];
        }

        return self::LEGACY_FALLBACK_CODE;
    }

    private function assignSequential(array &$plan, $group, string $prefix): void
    {
        $seq = 1;
        foreach ($group->sortBy('id') as $invoice) {
            $new = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $seq++;
            if ($new !== $invoice->invoiceno) {
                $plan[] = ['id' => $invoice->id, 'old' => $invoice->invoiceno, 'new' => $new];
            }
        }
    }
}
