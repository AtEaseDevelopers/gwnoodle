<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Product Quantity Sold Report" no longer filters by driver - just a date
 * range gathering every invoice in it. Drop the driver_id filter field so
 * it stops appearing on the report's form.
 */
class RemoveDriverFilterFromProductQtySoldReport extends Migration
{
    public function up()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'PRODUCT_QTY_SOLD_REPORT')->value('id');

        if ($reportId) {
            DB::table('reportdetails')
                ->where('report_id', $reportId)
                ->where('name', 'driver_id')
                ->delete();
        }
    }

    public function down()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'PRODUCT_QTY_SOLD_REPORT')->value('id');

        if ($reportId && !DB::table('reportdetails')->where('report_id', $reportId)->where('name', 'driver_id')->exists()) {
            $now = now();
            DB::table('reportdetails')->insert([
                'report_id' => $reportId,
                'name' => 'driver_id',
                'title' => 'Driver',
                'type' => 'multiselect',
                'data' => 'select id,name from drivers',
                'sequence' => 10,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
