<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKelindansTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kelindans', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('employeeid', 20);
            $table->string('name', 255);
            $table->string('ic', 20);
            $table->string('phone', 255)->nullable();
            $table->string('bankdetails1', 255);
            $table->string('bankdetails2', 255);
            $table->datetime('firstvaccine');
            $table->datetime('secondvaccine');
            $table->float('temperature', 10, 2);
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
        Schema::drop('kelindans');
    }
}
