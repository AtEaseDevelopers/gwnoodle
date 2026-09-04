<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds a "Include Zero Stock" dropdown to the Stock Balance Report's
 * "Run Report" form, wired to StockBalanceReportService's existing
 * show_zero_stock filter (which defaults to hiding zero-quantity rows -
 * see 0a40a92e). The `data` column isn't valid SQL on purpose: the
 * dynamic form builder in reports/show.blade.php tries it as a query
 * first and falls back to json_decode() on failure for dropdown/
 * multiselect fields, which is how a static option list is defined here.
 */
class AddShowZeroStockFilterToStockBalanceReport extends Migration
{
    public function up()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'STOCK_BALANCE_REPORT')->value('id');

        if (!$reportId) {
            return;
        }

        if (DB::table('reportdetails')->where('report_id', $reportId)->where('name', 'show_zero_stock')->exists()) {
            return;
        }

        $now = now();
        DB::table('reportdetails')->insert([
            'report_id' => $reportId,
            'name' => 'show_zero_stock',
            'title' => 'Include Zero Stock',
            'type' => 'dropdown',
            'data' => json_encode(['No' => '0', 'Yes' => '1']),
            'sequence' => 10,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'STOCK_BALANCE_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')->where('report_id', $reportId)->where('name', 'show_zero_stock')->delete();
        }
    }
}
