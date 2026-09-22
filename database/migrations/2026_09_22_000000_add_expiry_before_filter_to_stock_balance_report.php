<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds an "Expiring Before" date filter to the Stock Balance Report's
 * "Run Report" form, wired to StockBalanceReportService's expiry_before
 * filter (restricts to dated batches expiring on or before the chosen
 * date - i.e. stock nearing expiry). The field is optional: reports/
 * show.blade.php treats expiry_before as not-required, so leaving it blank
 * runs the full report. The report already sorts batches by nearest expiry.
 *
 * type=date renders the datetimepicker input (YYYY-MM-DD). Date fields
 * carry no option list, so `data` is null - show()'s SQL-then-JSON parsing
 * leaves date fields untouched.
 */
class AddExpiryBeforeFilterToStockBalanceReport extends Migration
{
    public function up()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'STOCK_BALANCE_REPORT')->value('id');

        if (!$reportId) {
            return;
        }

        if (DB::table('reportdetails')->where('report_id', $reportId)->where('name', 'expiry_before')->exists()) {
            return;
        }

        $now = now();
        DB::table('reportdetails')->insert([
            'report_id' => $reportId,
            'name' => 'expiry_before',
            'title' => 'Expiring Before',
            'type' => 'date',
            'data' => null,
            'sequence' => 11,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'STOCK_BALANCE_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')->where('report_id', $reportId)->where('name', 'expiry_before')->delete();
        }
    }
}
