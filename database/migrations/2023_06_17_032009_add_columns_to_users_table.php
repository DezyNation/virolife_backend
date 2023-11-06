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
            $table->string('phone_number')->after('email')->nullable();
            $table->string('gender')->nullable()->after('phone_number');
            $table->decimal('wallet', 16, 2)->after('gender')->default(0);
            $table->date('dob')->after('wallet')->nullable();
            $table->text('address')->after('dob')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wallet');
            $table->dropColumn('phone_number');
            $table->dropColumn('gender');
            $table->dropColumn('address');
        });
    }
};
