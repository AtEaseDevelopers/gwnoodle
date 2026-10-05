<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sales Analysis Report: the Item multiselect listed products by name, but
 * ReportController::show() keys dropdown options by their label - so OEM
 * variants sharing one display name overwrote each other and only a single
 * product id survived in the list. Picking the name could therefore filter
 * on the wrong variant's id and return an empty report even though invoices
 * exist in the range.
 *
 * Relabel the field "Unit code" and list options as "UNITCODE - Name",
 * which is unique per product, so every variant is selectable.
 */
class UpdateSalesAnalysisItemFilterToUnitCode extends Migration
{
    public function up()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'SALES_ANALYSIS_REPORT')->value('id');

        if (!$reportId) {
            return;
        }

        DB::table('reportdetails')
            ->where('report_id', $reportId)
            ->where('name', 'product_id')
            ->update([
                'title' => 'Unit code',
                // Column order matters: reports/show.blade.php uses column 1
                // as the option value and column 2 as the label.
                'data' => "select id, concat(coalesce(nullif(unit_code, ''), concat('#', id)), ' - ', name) from products where status = 1 order by unit_code, name",
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
        $reportId = DB::table('reports')->where('sqlvalue', 'SALES_ANALYSIS_REPORT')->value('id');

        if (!$reportId) {
            return;
        }

        DB::table('reportdetails')
            ->where('report_id', $reportId)
            ->where('name', 'product_id')
            ->update([
                'title' => 'Item',
                'data' => 'select id, name from products where status = 1 order by name',
                'updated_at' => now(),
            ]);
    }
}
