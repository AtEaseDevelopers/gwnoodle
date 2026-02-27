<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action'); // create, update, delete, bulk_update, etc.
            $table->string('module'); // e.g., 'assign', 'customer', 'driver', etc.
            $table->json('old_data')->nullable(); // Before changes
            $table->json('new_data')->nullable(); // After changes
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('user_id');
            $table->index('module');
            $table->index('action');
            $table->index('created_at');
            $table->index(['module']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};