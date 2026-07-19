<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: this will fail with a duplicate-key error if any duplicate
     * `invoiceno` values already exist in the table. Resolve those first -
     * see the duplicate-finder query in the project notes.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('invoiceno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoiceno']);
        });
    }
};
