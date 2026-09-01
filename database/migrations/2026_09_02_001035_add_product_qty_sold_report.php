<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers "Product Quantity Sold Report" in the Reports list, with the
 * same Date + Driver filter fields as "Daily Sales Report" (report_id 23) -
 * it reuses the same underlying data, just renders product+qty only, no
 * sales/payment figures. See ReportController::productQtySoldReportView().
 */
class AddProductQtySoldReport extends Migration
{
    public function up()
    {
        if (DB::table('reports')->where('sqlvalue', 'PRODUCT_QTY_SOLD_REPORT')->exists()) {
            return;
        }

        $now = now();

        $reportId = DB::table('reports')->insertGetId([
            'name' => 'Product Quantity Sold Report',
            'sqlvalue' => 'PRODUCT_QTY_SOLD_REPORT',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('reportdetails')->insert([
            [
                'report_id' => $reportId,
                'name' => 'driver_id',
                'title' => 'Driver',
                'type' => 'singleselect',
                'data' => 'select id,name from drivers',
                'sequence' => 10,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'report_id' => $reportId,
                'name' => 'date',
                'title' => 'Date',
                'type' => 'date',
                'data' => null,
                'sequence' => 10,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        $report = DB::table('reports')->where('sqlvalue', 'PRODUCT_QTY_SOLD_REPORT')->first();

        if ($report) {
            DB::table('reportdetails')->where('report_id', $report->id)->delete();
            DB::table('reports')->where('id', $report->id)->delete();
        }
    }
}
