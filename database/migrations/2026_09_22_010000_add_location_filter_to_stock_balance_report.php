<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds a "Location" dropdown to the Stock Balance Report's "Run Report"
 * form, wired to StockBalanceReportService's existing warehouse_id filter
 * (which limits the report to a single warehouse, or all warehouses when
 * falsy). Each active warehouse is one location - e.g. "NO 18" / "NO 23".
 *
 * type=singleselect: reports/show.blade.php renders a required single-select
 * with no "None"/"Pick a..." placeholder and preselects the first option, so
 * "All location" (value 0) is the natural default. A non-empty value (0)
 * rather than '' is used so HTML5 required-validation still passes when the
 * default "All location" is submitted; the service treats 0 as falsy and
 * reports every warehouse.
 *
 * ReportController::show()'s JSON-fallback parsing (for when `data` isn't
 * valid SQL) only applies to multiselect/dropdown, not singleselect - so
 * `data` here must be a real SQL query returning (value, label). The ord
 * column keeps "All location" first and orders the warehouses by name
 * (UNION doesn't guarantee order on its own); only the first two result
 * columns are actually used.
 */
class AddLocationFilterToStockBalanceReport extends Migration
{
    public function up()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'STOCK_BALANCE_REPORT')->value('id');

        if (!$reportId) {
            return;
        }

        if (DB::table('reportdetails')->where('report_id', $reportId)->where('name', 'warehouse_id')->exists()) {
            return;
        }

        $now = now();
        DB::table('reportdetails')->insert([
            'report_id' => $reportId,
            'name' => 'warehouse_id',
            'title' => 'Location',
            'type' => 'singleselect',
            'data' => "SELECT 0 AS v, 'All location' AS l, 0 AS ord UNION SELECT id AS v, name AS l, 1 AS ord FROM warehouses WHERE status = 'active' ORDER BY ord, l",
            'sequence' => 5,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'STOCK_BALANCE_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')->where('report_id', $reportId)->where('name', 'warehouse_id')->delete();
        }
    }
}
