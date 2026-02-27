<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTransfersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('from_driver_id', false);
            $table->bigInteger('from_lorry_id', false);
            $table->bigInteger('to_driver_id', false);
            $table->bigInteger('to_lorry_id', false);
            $table->bigInteger('product_id', false);
            $table->bigInteger('quantity', false);
            $table->bigInteger('status', false);
            $table->string('remark', 255);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('inventory_transfers');
    }
}
