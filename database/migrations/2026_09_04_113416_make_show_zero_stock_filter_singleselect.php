<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "Include Zero Stock" field was type=dropdown, which always renders a
 * "Pick a show_zero_stock..." placeholder as the first option regardless of
 * required-ness. Switching to type=singleselect drops that placeholder
 * entirely for a required field (see reports/show.blade.php's singleselect
 * branch), and listing "No" first makes it the natural default-selected
 * option with no other change needed.
 *
 * ReportController::show()'s JSON-fallback parsing (for when `data` isn't
 * valid SQL) only applies to multiselect/dropdown, not singleselect - so
 * unlike the dropdown version, `data` here has to be a real SQL query. The
 * ord column keeps row order deterministic (UNION doesn't guarantee it on
 * its own); only the first two result columns are actually used.
 */
class MakeShowZeroStockFilterSingleselect extends Migration
{
    public function up()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'STOCK_BALANCE_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')
                ->where('report_id', $reportId)
                ->where('name', 'show_zero_stock')
                ->update([
                    'type' => 'singleselect',
                    'data' => "SELECT 0 AS v, 'No' AS l, 1 AS ord UNION SELECT 1, 'Yes', 2 ORDER BY ord",
                ]);
        }
    }

    public function down()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'STOCK_BALANCE_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')
                ->where('report_id', $reportId)
                ->where('name', 'show_zero_stock')
                ->update([
                    'type' => 'dropdown',
                    'data' => json_encode(['No' => '0', 'Yes' => '1']),
                ]);
        }
    }
}
