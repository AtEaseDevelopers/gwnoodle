<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * On some environments the `migrations` table's `id` column was created
 * without AUTO_INCREMENT (and possibly without a PRIMARY KEY), so the
 * migrator's own log insert after each migration fails with "Field 'id'
 * doesn't have a default value" even though the migration itself ran fine.
 *
 * This fixes that here, before any later migration in this run needs to log
 * itself. It repairs its own table, so it self-heals: its up() fixes the
 * column, then the migrator's log() call for this very migration succeeds.
 */
class FixMigrationsTableIdAutoIncrement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $column = collect(DB::select("SHOW COLUMNS FROM `migrations` WHERE Field = 'id'"))->first();

        if (!$column || stripos($column->Extra, 'auto_increment') !== false) {
            return;
        }

        $hasPrimaryKey = collect(DB::select("SHOW KEYS FROM `migrations` WHERE Key_name = 'PRIMARY'"))->isNotEmpty();

        if (!$hasPrimaryKey) {
            DB::statement('ALTER TABLE `migrations` ADD PRIMARY KEY (`id`)');
        }

        DB::statement('ALTER TABLE `migrations` MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Intentionally left as a no-op: reverting AUTO_INCREMENT would
        // reintroduce the bug this migration exists to fix.
    }
}
