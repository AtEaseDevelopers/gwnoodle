<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * Sales Analysis Report filters invoices by status+date range and invoice
 * lines by product_id - neither was indexed (only invoices.status+
 * customer_id and invoice_details.invoice_id were), so on a real invoice
 * history this query falls back to a full table scan. Add the two indexes
 * it actually needs.
 */
class AddIndexesForSalesAnalysisReport extends Migration
{
    public function up()
    {
        if (!$this->indexExists('invoices', 'idx_invoices_status_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['status', 'date'], 'idx_invoices_status_date');
            });
        }

        if (!$this->indexExists('invoice_details', 'idx_invoice_details_product_id')) {
            Schema::table('invoice_details', function (Blueprint $table) {
                $table->index('product_id', 'idx_invoice_details_product_id');
            });
        }
    }

    public function down()
    {
        if ($this->indexExists('invoices', 'idx_invoices_status_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex('idx_invoices_status_date');
            });
        }

        if ($this->indexExists('invoice_details', 'idx_invoice_details_product_id')) {
            Schema::table('invoice_details', function (Blueprint $table) {
                $table->dropIndex('idx_invoice_details_product_id');
            });
        }
    }

    private function indexExists($table, $indexName)
    {
        $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($result) > 0;
    }
}
