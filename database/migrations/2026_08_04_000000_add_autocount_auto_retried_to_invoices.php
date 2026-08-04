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
            // Tracks whether a 'failed' autocount status has already been
            // auto-requeued once. Prevents an endless retry loop.
            $table->boolean('autocount_auto_retried')->default(false)->after('autocount_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('autocount_auto_retried');
        });
    }
};
