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
        Schema::create('redemptions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('redeemable_item_id')->constrained()->cascadeOnDelete();
        $table->foreignId('business_id')->constrained()->cascadeOnDelete();
        $table->string('item_name');
        $table->string('business_name');
        $table->string('city')->nullable();
        $table->integer('coins_spent');
        $table->enum('status', ['pending', 'completed', 'confirmed'])->default('pending');
        $table->timestamps();
        });
		
		
		Schema::create('coin_transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->enum('type', ['earned', 'redeemed']);
        $table->integer('points');
        $table->string('description');
        $table->timestamps();
    });

    // Add coins balance to users
    Schema::table('users', function (Blueprint $table) {
        $table->integer('coin_balance')->default(0);
        $table->integer('total_earned')->default(0);
        $table->integer('total_redeemed')->default(0);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redemptions');
    }
};
