<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameClassificationCodeToUomInProductsTable extends Migration
{
    /**
     * The `classification_code` column has actually been holding each
     * product's unit of measure (synced in from AutoCount's UOM field) -
     * not a MyInvois classification code. Rename it to `uom` to match what
     * it really stores, then add a fresh `classification_code` column for
     * the genuine MyInvois classification going forward.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('classification_code', 'uom');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('classification_code', 255)->nullable()->after('uom');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('classification_code');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('uom', 'classification_code');
        });
    }
}
