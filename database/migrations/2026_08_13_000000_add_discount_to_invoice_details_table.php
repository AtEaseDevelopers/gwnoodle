<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add an optional, fixed-amount, per-line discount to invoice lines.
 *
 * The value is a whole-line RM amount (not per-unit, not a percentage): the net
 * line total becomes `quantity * price - discount`. It is double(10,3) to match
 * the price/totalprice columns widened in 2026_08_05, and defaults to 0 so every
 * existing line and every un-discounted line behaves exactly as before.
 *
 * Raw ALTER (not Blueprint) mirrors the widen migration: doctrine/dbal cannot
 * introspect every column type on this table.
 */
class AddDiscountToInvoiceDetailsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('invoice_details', 'discount')) {
            DB::statement("ALTER TABLE `invoice_details` ADD `discount` DOUBLE(10, 3) NOT NULL DEFAULT 0 AFTER `price`");
        }
    }

    public function down()
    {
        if (Schema::hasColumn('invoice_details', 'discount')) {
            DB::statement("ALTER TABLE `invoice_details` DROP COLUMN `discount`");
        }
    }
}
