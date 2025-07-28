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
        Schema::create('milestone_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained()->onDelete('cascade');
            $table->string('item_id'); // e.g., "minecraft:oak_planks"
            $table->string('display_name')->nullable(); // e.g., "Oak Planks"
            $table->string('recipe_id_to_ban'); // e.g., "minecraft:oak_planks"
            $table->integer('shop_price')->nullable(); // Nullable if not available in shop
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestone_unlocks');
    }
};
