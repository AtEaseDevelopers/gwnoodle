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
            // Add the column after 'status' and make it nullable
            $table->string('autocount_status', 50)->default('pending')->after('status');
            // Add index
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

            // Drop the column
            $table->dropColumn('autocount_status');
        });
    }
};