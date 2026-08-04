<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddStatusToAssignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // The column was originally added directly to some databases, so guard
        // against re-adding it while still covering fresh installs.
        if (! Schema::hasColumn('assigns', 'status')) {
            Schema::table('assigns', function (Blueprint $table) {
                // 1 = active (visible to driver), 0 = inactive (hidden from driver).
                $table->tinyInteger('status')->default(1)->after('sequence');
            });
        }

        // Backfill: an assign whose customer is currently inactive
        // (customers.status != 1) should itself be inactive so the driver
        // stops seeing it. Active customers keep the default active assign.
        DB::table('assigns')
            ->join('customers', 'assigns.customer_id', '=', 'customers.id')
            ->where('customers.status', '!=', 1)
            ->update(['assigns.status' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('assigns', 'status')) {
            Schema::table('assigns', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
}
