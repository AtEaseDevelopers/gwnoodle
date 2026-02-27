<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicePaymentsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->integer('invoice_id');
            $table->bigInteger('type', false);
            $table->integer('customer_id');
            $table->float('amount', 10, 2);
            $table->bigInteger('status', false);
            $table->string('attachment', 255);
            $table->string('approve_by', 255);
            $table->datetime('approve_at');
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
        Schema::drop('invoice_payments');
    }
}
