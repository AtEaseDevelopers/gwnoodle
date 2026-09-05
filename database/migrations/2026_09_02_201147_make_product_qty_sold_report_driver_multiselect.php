<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Product Quantity Sold Report" only let you pick one driver at a time.
 * Switch its driver_id field to multiselect so several drivers - or all of
 * them via the picker's "Select All" button - can be chosen at once. No
 * downstream code change needed: generateReportByDateRange() already
 * handles driver_id as an array.
 */
class MakeProductQtySoldReportDriverMultiselect extends Migration
{
    public function up()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'PRODUCT_QTY_SOLD_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')
                ->where('report_id', $reportId)
                ->where('name', 'driver_id')
                ->update(['type' => 'multiselect']);
        }
    }

    public function down()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'PRODUCT_QTY_SOLD_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')
                ->where('report_id', $reportId)
                ->where('name', 'driver_id')
                ->update(['type' => 'singleselect']);
        }
    }
}
