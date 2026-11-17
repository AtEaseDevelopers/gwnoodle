<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers "Stock Transfer Report" in the Reports list.
 *
 * Filters: From Location, To Location (singleselect, "All location" = 0),
 * Product (multiselect, ALL option), Product Batch (optional - the title
 * "Product Batch" is what reports/show.blade.php uses to drop "required"),
 * Date From, Date To. See ReportController::stockTransferReportView() /
 * StockTransferReportService.
 */
class AddStockTransferReport extends Migration
{
    public function up()
    {
        if (DB::table('reports')->where('sqlvalue', 'STOCK_TRANSFER_REPORT')->exists()) {
            return;
        }

        $now = now();

        $reportId = DB::table('reports')->insertGetId([
            'name' => 'Stock Transfer Report',
            'sqlvalue' => 'STOCK_TRANSFER_REPORT',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // All warehouses, not just active ones, so past transfers stay reportable.
        $locations = "SELECT 0 AS v, 'All location' AS l, 0 AS ord UNION SELECT id AS v, name AS l, 1 AS ord FROM warehouses ORDER BY ord, l";

        $row = function ($name, $title, $type, $data, $sequence, $udf1 = null) use ($reportId, $now) {
            return [
                'report_id' => $reportId,
                'name' => $name,
                'title' => $title,
                'type' => $type,
                'data' => $data,
                'sequence' => $sequence,
                'status' => 1,
                'STR_UDF1' => $udf1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        DB::table('reportdetails')->insert([
            $row('from_warehouse_id', 'From Location', 'singleselect', $locations, 10),
            $row('to_warehouse_id', 'To Location', 'singleselect', $locations, 20),
            // Options are keyed by label in ReportController::show(), so labels
            // must be unique: product name + unit code, and distinct batch codes
            // (the service filters batches by code). Inactive products are kept
            // so their past transfers can still be picked.
            $row('product_id', 'Product', 'multiselect', "select id as v, concat(name, ' (', coalesce(unit_code, '-'), ')') as l from products order by l", 30, 'Y'),
            $row('batch_id', 'Product Batch', 'multiselect', 'select distinct batch_code as v, batch_code as l from product_batches where batch_code is not null order by l', 40),
            $row('datefrom', 'Date From', 'date', null, 50),
            $row('dateto', 'Date To', 'date', null, 60),
        ]);
    }

    public function down()
    {
        $report = DB::table('reports')->where('sqlvalue', 'STOCK_TRANSFER_REPORT')->first();

        if ($report) {
            DB::table('reportdetails')->where('report_id', $report->id)->delete();
            DB::table('reports')->where('id', $report->id)->delete();
        }
    }
}
