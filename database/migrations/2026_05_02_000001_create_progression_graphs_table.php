<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('progression_graphs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon_item_id')->default('minecraft:book');
            $table->json('categories')->nullable(); // e.g., ["Magic", "Techno"]
            $table->timestamps();
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->foreignId('graph_id')->nullable()->constrained('progression_graphs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropForeign(['graph_id']);
            $table->dropColumn('graph_id');
        });
        Schema::dropIfExists('progression_graphs');
    }
};
