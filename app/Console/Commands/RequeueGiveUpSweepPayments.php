<?php

namespace App\Console\Commands;

use App\Models\InvoicePayment;
use Illuminate\Console\Command;

/**
 * One-time cleanup for invoice-payments that were auto-failed by the old
 * "give-up sweep" (a batch that stayed pending past a timeout was marked
 * 'failed' even though nothing was actually wrong - its invoice just had not
 * been approved in AutoCount yet). That sweep has been removed, so these rows
 * should go back to 'pending' and resume syncing on their own once the invoice
 * is approved.
 *
 * The sweep is the ONLY writer of the "web give-up sweep" marker in
 * autocount_message, so matching on it targets exactly those rows and leaves
 * genuine sync failures (plugin-reported errors) untouched. Only rows currently
 * 'failed' are considered, so re-running is safe. Use --dry-run first.
 */
class RequeueGiveUpSweepPayments extends Command
{
    protected $signature = 'payments:requeue-giveup-sweep
        {--dry-run : Show what would change without writing anything}';

    protected $description = "Reset 'failed' invoice-payments that were auto-failed by the removed give-up sweep back to 'pending'";

    public function handle()
    {
        $query = InvoicePayment::where('autocount_status', InvoicePayment::AC_FAILED)
            ->where('autocount_message', 'like', '%web give-up sweep%');

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No give-up-sweep failures found. Nothing to do.');
            return 0;
        }

        $this->info("Give-up-sweep failures to requeue -> pending: {$count}");

        if ($this->option('dry-run')) {
            $this->warn('[DRY RUN] No changes written. Re-run without --dry-run to apply.');
            return 0;
        }

        if (!$this->confirm("Reset {$count} payment(s) back to pending?", false)) {
            $this->info('Aborted. Nothing changed.');
            return 0;
        }

        $updated = $query->update([
            'autocount_status'  => InvoicePayment::AC_PENDING,
            'autocount_message' => null,
        ]);

        $this->info("Done. Requeued {$updated} payment(s) to pending.");
        return 0;
    }
}
