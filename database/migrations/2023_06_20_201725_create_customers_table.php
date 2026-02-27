<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('code', 255);
            $table->string('name', 255);
            $table->bigInteger('paymentterm', false);
            $table->integer('agent_id');
            $table->integer('supervisor_id');
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
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
        Schema::drop('customers');
    }
}
