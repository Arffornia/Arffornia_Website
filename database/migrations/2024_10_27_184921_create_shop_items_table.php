<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("description");
            $table->string("img_url");
            $table->enum('payment_type', ['coins', 'real_money'])->default('coins');

            $table->integer('price');
            $table->integer('promo_price')->nullable();
            $table->string('currency', 3)->nullable()->comment('e.g., EUR, USD for real_money type');

            $table->unsignedInteger('coins_awarded')->nullable()->comment('Amount of virtual currency awarded for real_money purchases');

            $table->boolean("is_unique")->default(false);
            $table->boolean('show_in_newest')->default(true);
            $table->boolean('allow_discounts')->default(true);

            $table->json('commands')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_items');
    }
};
