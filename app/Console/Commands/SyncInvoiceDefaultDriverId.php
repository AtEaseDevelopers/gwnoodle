<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class SyncInvoiceDefaultDriverId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:sync-default-driver-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill missing invoice default_driver_id from the related customer driver_id';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $updatedCount = 0;

        Invoice::query()
            ->whereNull('default_driver_id')
            ->whereNotNull('customer_id')
            ->with(['customer:id,driver_id'])
            ->orderBy('id')
            ->chunkById(500, function ($invoices) use (&$updatedCount) {
                foreach ($invoices as $invoice) {
                    $customerDriverId = $invoice->customer->driver_id ?? null;

                    if ($customerDriverId === null) {
                        continue;
                    }

                    $invoice->default_driver_id = $customerDriverId;
                    $invoice->save();
                    $updatedCount++;
                }
            });

        $this->info("Updated {$updatedCount} invoice(s) default_driver_id.");

        return 0;
    }
}
