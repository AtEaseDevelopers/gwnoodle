<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFocsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('focs', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('product_id', false);
            $table->bigInteger('customer_id', false);
            $table->integer('quantity');
            $table->bigInteger('free_product_id', false);
            $table->integer('free_quantity');
            $table->datetime('startdate');
            $table->datetime('enddate');
            $table->bigInteger('status', false);
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
        Schema::drop('focs');
    }
}
