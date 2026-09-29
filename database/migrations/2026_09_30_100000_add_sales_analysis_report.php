<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers "Sales Analysis Report" in the Reports list.
 *
 * Filters: Customer (multiselect, ALL option), Item (multiselect, ALL
 * option), Date From, Date To. Lists every invoice line in range - qty,
 * unit price and total price - grouped by customer, with subtotals and a
 * grand total. See ReportController::salesAnalysisReportView() /
 * SalesAnalysisReportService.
 */
class AddSalesAnalysisReport extends Migration
{
    public function up()
    {
        if (DB::table('reports')->where('sqlvalue', 'SALES_ANALYSIS_REPORT')->exists()) {
            return;
        }

        $now = now();

        $reportId = DB::table('reports')->insertGetId([
            'name' => 'Sales Analysis Report',
            'sqlvalue' => 'SALES_ANALYSIS_REPORT',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('reportdetails')->insert([
            [
                'report_id' => $reportId,
                'name' => 'customer_id',
                'title' => 'Customer',
                'type' => 'multiselect',
                'data' => 'select id, company from customers where status = 1 order by company',
                'sequence' => 10,
                'status' => 1,
                'STR_UDF1' => 'Y', // adds the "ALL" option in reports/show.blade.php
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'report_id' => $reportId,
                'name' => 'product_id',
                'title' => 'Item',
                'type' => 'multiselect',
                'data' => 'select id, name from products where status = 1 order by name',
                'sequence' => 20,
                'status' => 1,
                'STR_UDF1' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'report_id' => $reportId,
                'name' => 'datefrom',
                'title' => 'Date From',
                'type' => 'date',
                'data' => null,
                'sequence' => 30,
                'status' => 1,
                'STR_UDF1' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'report_id' => $reportId,
                'name' => 'dateto',
                'title' => 'Date To',
                'type' => 'date',
                'data' => null,
                'sequence' => 40,
                'status' => 1,
                'STR_UDF1' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        $report = DB::table('reports')->where('sqlvalue', 'SALES_ANALYSIS_REPORT')->first();

        if ($report) {
            DB::table('reportdetails')->where('report_id', $report->id)->delete();
            DB::table('reports')->where('id', $report->id)->delete();
        }
    }
}
