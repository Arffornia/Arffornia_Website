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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('solo_progression_id')->nullable()->constrained('progressions');
            $table->foreignId('active_progression_id')->nullable()->constrained('progressions');
            $table->uuid('team_id')->nullable()->constrained('teams')->onDelete('set null');

            $table->string("name");
            $table->string("uuid");
            $table->string("role");
            $table->string("grade");
            $table->float("money");
            $table->unsignedBigInteger("progress_point");
            $table->unsignedBigInteger("stage_id");
            $table->unsignedInteger('day_streak');
            $table->dateTime('last_connexion')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
