<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('cost', 15, 2);
            $table->decimal('old_cost', 15, 2)->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'created_at']);
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_cost');
    }
}
