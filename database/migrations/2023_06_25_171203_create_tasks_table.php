<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->datetime('date');
            $table->bigInteger('driver_id', false);
            $table->bigInteger('customer_id', false);
            $table->bigInteger('sequence', false);
            $table->bigInteger('invoice_id', false);
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
        Schema::drop('tasks');
    }
}
