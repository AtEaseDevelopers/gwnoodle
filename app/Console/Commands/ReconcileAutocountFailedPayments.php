<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\autocount_plugin\PaymentController;
use App\Models\InvoicePayment;
use Illuminate\Console\Command;

/**
 * One-time cleanup for invoice-payments stuck on autocount_status='failed' whose
 * failure reason is really "the invoice is already fully paid in AutoCount"
 * (nothing left to knock off). These are not sync errors - the money is already
 * reflected in AutoCount - so they should not sit on the "AutoCount failed" badge.
 *
 * Reclassification (mirrors the live /api/payment/update path):
 *   - already-paid AND has an OR DocNo (payment_no) -> 'success' (the receipt is real)
 *   - already-paid AND no OR                         -> 'skipped' (terminal, review in AutoCount)
 *   - any other failure reason                       -> left untouched (genuine failure)
 *
 * Only rows currently 'failed' are considered, so re-running is safe. Use --dry-run first.
 */
class ReconcileAutocountFailedPayments extends Command
{
    protected $signature = 'payments:reconcile-autocount-failed
        {--dry-run : Show what would change without writing anything}';

    protected $description = "Reclassify 'failed' invoice-payments that only failed because the invoice was already fully paid in AutoCount";

    public function handle()
    {
        $failed = InvoicePayment::where('autocount_status', InvoicePayment::AC_FAILED)->get();

        if ($failed->isEmpty()) {
            $this->info('No failed invoice-payments found. Nothing to do.');
            return 0;
        }

        $alreadyPaid = $failed->filter(
            fn ($r) => PaymentController::messageMeansAlreadyPaid($r->autocount_message)
        );

        $toSuccess = $alreadyPaid->filter(fn ($r) => !empty($r->payment_no));
        $toSkipped = $alreadyPaid->reject(fn ($r) => !empty($r->payment_no));
        $genuine   = $failed->count() - $alreadyPaid->count();

        $this->info("Failed invoice-payments: {$failed->count()}");
        $this->line("  -> success (already paid, has OR): {$toSuccess->count()}");
        $this->line("  -> skipped (already paid, no OR):  {$toSkipped->count()}");
        $this->line("  left as failed (genuine errors):   {$genuine}");

        if ($this->option('dry-run')) {
            $this->warn('[DRY RUN] No changes written. Re-run without --dry-run to apply.');
            return 0;
        }

        if (!$this->confirm("Reclassify {$alreadyPaid->count()} already-paid payment(s)?", false)) {
            $this->info('Aborted. Nothing changed.');
            return 0;
        }

        foreach ($toSuccess as $r) {
            InvoicePayment::where('id', $r->id)->update(['autocount_status' => InvoicePayment::AC_SUCCESS]);
        }
        foreach ($toSkipped as $r) {
            InvoicePayment::where('id', $r->id)->update(['autocount_status' => InvoicePayment::AC_SKIPPED]);
        }

        $this->info("Done. success={$toSuccess->count()}, skipped={$toSkipped->count()}, untouched={$genuine}.");
        return 0;
    }
}
