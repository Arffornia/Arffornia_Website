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
        Schema::create('milestone_closure', function (Blueprint $table) {
            $table->unsignedBigInteger("milestone_id");
            $table->unsignedBigInteger("descendant_id");
            $table->boolean("is_root");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestone_closure');
    }
};
