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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('primary_activated')->default(0)->nullable();
            $table->boolean('secondary_activated')->default(0)->nullable();
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->string('video_id')->unique()->nullable();
            $table->string('provider')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
