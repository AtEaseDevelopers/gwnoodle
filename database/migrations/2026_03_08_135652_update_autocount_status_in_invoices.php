<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add the status column after 'status' and make it nullable
            $table->string('autocount_status', 50)->default('pending')->after('status');
            
            // Add the message column after 'autocount_status'
            $table->longText('autocount_message')->nullable()->after('autocount_status');
            
            // Add index for faster querying
            $table->index('autocount_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex(['autocount_status']);

            // Drop the columns (an array can be used to drop multiple columns at once)
            $table->dropColumn(['autocount_message', 'autocount_status']);
        });
    }
};