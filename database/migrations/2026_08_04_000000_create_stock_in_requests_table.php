<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockInRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_in_requests', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30)->default('bulk_scan'); // bulk_scan | batch_create
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('batch_id');
            $table->integer('requested_quantity'); // original requested qty (audit)
            $table->integer('quantity');            // current/approved qty (editable before approval)
            $table->string('remark', 255)->nullable();
            $table->tinyInteger('status')->default(0); // 0 pending, 1 approved, 2 rejected
            $table->string('approval_remark', 255)->nullable();
            $table->string('requested_by', 255)->nullable();
            $table->string('reviewed_by', 255)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_in_requests');
    }
}
