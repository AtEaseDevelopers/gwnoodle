<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('invoiceno', 255);
            $table->datetime('date');
            $table->integer('customer_id');
            $table->integer('driver_id');
            $table->integer('kelindan_id');
            $table->integer('agent_id');
            $table->integer('supervisor_id');
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
        Schema::drop('invoices');
    }
}
