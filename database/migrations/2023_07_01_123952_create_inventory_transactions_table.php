<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTransactionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('lorry_id', false);
            $table->bigInteger('product_id', false);
            $table->bigInteger('quantity', false);
            $table->bigInteger('type', false);
            $table->string('remark', 255);
            $table->string('user', 255);
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
        Schema::drop('inventory_transactions');
    }
}
