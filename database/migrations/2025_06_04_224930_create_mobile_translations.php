<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileTranslations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id');
            $table->string('key')->index(); // e.g. "app.status.text.new"
            $table->text('value'); // Translated text
            $table->timestamps();
            
            $table->unique(['language_id', 'key']);
        });

        Schema::create('mobile_translation_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id');
            $table->integer('version')->default(1);
            $table->timestamps();
            
            $table->unique(['language_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mobile_translations');
    }
}
