<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Customer/Item multiselects on Sales Analysis Report already have a
 * "Select All" button (bootstrap-select's built-in action), which did the
 * same thing as the separate "ALL" option - drop the "ALL" option so there
 * is only one way to select everything.
 */
class RemoveAllOptionFromSalesAnalysisReport extends Migration
{
    public function up()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'SALES_ANALYSIS_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')
                ->where('report_id', $reportId)
                ->whereIn('name', ['customer_id', 'product_id'])
                ->update(['STR_UDF1' => null]);
        }
    }

    public function down()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'SALES_ANALYSIS_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')
                ->where('report_id', $reportId)
                ->whereIn('name', ['customer_id', 'product_id'])
                ->update(['STR_UDF1' => 'Y']);
        }
    }
}
