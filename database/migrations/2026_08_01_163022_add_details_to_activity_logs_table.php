<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // LogsActivity trait has always written these, but the table
            // never had columns for them, so they were silently dropped by
            // mass-assignment protection - record_id in particular means
            // there was no way to tell WHICH record a log entry was about.
            $table->string('description')->nullable()->after('module');
            $table->string('table_name')->nullable()->after('description');
            $table->unsignedBigInteger('record_id')->nullable()->after('table_name');
            $table->string('ip_address')->nullable()->after('new_data');
            $table->string('user_agent')->nullable()->after('ip_address');

            $table->index('record_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['record_id']);
            $table->dropColumn(['description', 'table_name', 'record_id', 'ip_address', 'user_agent']);
        });
    }
}
