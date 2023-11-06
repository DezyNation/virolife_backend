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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('transaction_id');
            $table->morphs('purchasable');
            $table->decimal('credit', 16, 2);
            $table->decimal('debit', 16, 2);
            $table->decimal('opening_balance', 16, 2);
            $table->decimal('closing_balance', 16, 2);
            $table->json('metadata');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
