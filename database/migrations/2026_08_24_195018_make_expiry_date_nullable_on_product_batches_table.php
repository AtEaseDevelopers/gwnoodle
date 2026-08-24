<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Product batches created for non-perishable products don't have a real
 * expiry date. The column was NOT NULL with no default, forcing every
 * batch to carry a (sometimes meaningless) date. Raw ALTER mirrors other
 * migrations in this file: doctrine/dbal cannot introspect every column
 * type present on this table.
 */
class MakeExpiryDateNullableOnProductBatchesTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('product_batches', 'expiry_date')) {
            DB::statement('ALTER TABLE `product_batches` MODIFY `expiry_date` DATE NULL');
        }
    }

    public function down()
    {
        if (Schema::hasColumn('product_batches', 'expiry_date')) {
            DB::statement('ALTER TABLE `product_batches` MODIFY `expiry_date` DATE NOT NULL');
        }
    }
}
