<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddProductSnapshotToInvoiceDetailsTable extends Migration
{
    public function up()
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_details', 'product_name')) {
                $table->string('product_name')->nullable();
            }
            if (!Schema::hasColumn('invoice_details', 'product_code')) {
                $table->string('product_code', 50)->nullable();
            }
            if (!Schema::hasColumn('invoice_details', 'uom')) {
                $table->string('uom')->nullable();
            }
            if (!Schema::hasColumn('invoice_details', 'classification_code')) {
                $table->string('classification_code')->nullable();
            }
            if (!Schema::hasColumn('invoice_details', 'cost')) {
                $table->double('cost', 10, 2)->nullable();
            }
        });

        // Backfill existing lines with the product's CURRENT values. Only
        // touches rows not yet snapshotted, so it is safe to re-run.
        DB::statement('
            UPDATE invoice_details d
            JOIN products p ON p.id = d.product_id
            SET d.product_name = p.name,
                d.product_code = p.unit_code,
                d.uom = p.uom,
                d.classification_code = p.classification_code,
                d.cost = p.cost
            WHERE d.product_name IS NULL
        ');
    }

    public function down()
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'product_code', 'uom', 'classification_code', 'cost']);
        });
    }
}
