<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductBatch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('batch_code');
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('qr_code')->unique()->nullable();
            $table->text('barcode_data')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            
            $table->index(['product_id', 'batch_code']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_batch');
    }
}
