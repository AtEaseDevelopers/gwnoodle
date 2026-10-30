<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneApiLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronJob:pruneapilogs {--dry-run : Only count the rows that would be deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete api_logs rows older than the start of last month (keeps this month and last month)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // startOfMonth() first: now()->subMonth() on the 31st overflows (31 Sep -> 1 Oct)
        // and would delete last month too.
        $cutoff = now()->startOfMonth()->subMonth();

        // created_at has no index but follows id order (set to now() on insert), so find the
        // first id to keep once and delete by primary key below it instead of scanning by date.
        $keepFromId = DB::table('api_logs')
            ->where('created_at', '>=', $cutoff)
            ->orderBy('id')
            ->value('id');

        if ($keepFromId === null) {
            $keepFromId = (int) DB::table('api_logs')->max('id') + 1;
        }

        if ($this->option('dry-run')) {
            $count = DB::table('api_logs')->where('id', '<', $keepFromId)->count();
            $this->info("Dry run: {$count} api_logs rows before {$cutoff->toDateTimeString()} (id < {$keepFromId}) would be deleted.");
            return 0;
        }

        $deleted = 0;
        do {
            $affected = DB::table('api_logs')
                ->where('id', '<', $keepFromId)
                ->limit(5000)
                ->delete();
            $deleted += $affected;
        } while ($affected > 0);

        $message = "Deleted {$deleted} api_logs rows before {$cutoff->toDateTimeString()} (id < {$keepFromId}).";
        $this->info($message);
        Log::info($message);

        return 0;
    }
}
