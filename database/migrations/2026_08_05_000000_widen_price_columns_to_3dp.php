<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen unit-price and invoice line-total columns from 2 to 3 decimal places
 * so per-unit prices can be entered/stored with 3dp precision (e.g. 1.235).
 *
 * Scope: unit prices + invoice line totals only. Loans, commissions, payroll,
 * product cost and invoice payment amounts are intentionally left at 2dp.
 * The invoice grand total is computed, not stored, so it needs no column change.
 *
 * Columns are double(10,2) today; we keep the double family and only widen the
 * scale to 3. Raw ALTER (not Blueprint->change()) is used because doctrine/dbal
 * cannot introspect every column type present on these tables. All four columns
 * are NOT NULL with no default, so we preserve that in the MODIFY.
 */
class WidenPriceColumnsTo3dp extends Migration
{
    /**
     * price/totalprice columns keyed by table.
     *
     * @var array<string, string[]>
     */
    private array $columns = [
        'products'        => ['price'],
        'invoice_details' => ['price', 'totalprice'],
        'special_prices'  => ['price'],
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->setScale(3);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->setScale(2);
    }

    private function setScale(int $scale): void
    {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` DOUBLE(10, {$scale}) NOT NULL");
            }
        }
    }
}
