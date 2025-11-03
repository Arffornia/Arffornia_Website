<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypal_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('paypal_order_id')->unique();
            $table->string('status');
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3);
            $table->integer('coins_purchased');
            $table->text('paypal_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypal_transactions');
    }
};
