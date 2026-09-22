<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPasswordDisplayToUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'password_display')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password_display')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'password_display')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_display');
            });
        }
    }
}
