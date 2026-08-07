<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds AutoCount AR Payment (Official Receipt) sync tracking to invoice_payments.
 *
 * - payment_batch_id groups the N rows created by one payment event (one cheque
 *   over several invoices) so they sync as ONE AR Payment that knocks off all of
 *   the invoices, instead of one fragmented OR per invoice.
 * - payment_no / doc_id store AutoCount's OR DocNo / DocKey so a resync EDITS the
 *   existing receipt instead of creating a duplicate.
 * - autocount_status drives the queue: 'pending' (auto, non-credit), 'hold'
 *   (credit - waits for a manual web click), 'success', 'failed', 'skipped'.
 *
 * Guarded with hasColumn so it is safe on databases where the schema was applied
 * by hand (this project does not run migrations as the source of truth).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_payments', 'payment_batch_id')) {
                $table->char('payment_batch_id', 36)->nullable()->after('invoice_id')->index();
            }
            if (!Schema::hasColumn('invoice_payments', 'autocount_status')) {
                // Existing rows stay NULL so historical payments are never retro-synced.
                $table->string('autocount_status', 50)->nullable()->after('status')->index();
            }
            if (!Schema::hasColumn('invoice_payments', 'autocount_message')) {
                $table->longText('autocount_message')->nullable()->after('autocount_status');
            }
            if (!Schema::hasColumn('invoice_payments', 'payment_no')) {
                $table->string('payment_no', 255)->nullable()->after('autocount_message');
            }
            if (!Schema::hasColumn('invoice_payments', 'doc_id')) {
                $table->bigInteger('doc_id')->nullable()->after('payment_no');
            }
            if (!Schema::hasColumn('invoice_payments', 'autocount_auto_retried')) {
                // Default false so a failed sync is auto-requeued exactly once.
                // Existing rows have a NULL autocount_status so they never sync.
                $table->boolean('autocount_auto_retried')->default(false)->after('doc_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            foreach (['payment_batch_id', 'autocount_status', 'autocount_message', 'payment_no', 'doc_id', 'autocount_auto_retried'] as $col) {
                if (Schema::hasColumn('invoice_payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
