<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 017: the editable tables become the working draft (keys, thresholds, validations), published
 * snapshots are frozen JSON documents, and progression state is stored by milestone key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            $table->decimal('threshold', 4, 3)->default(0.8);
            $table->json('module')->nullable();
            $table->string('name_fr')->nullable();
            $table->text('description_fr')->nullable();
        });

        Schema::table('progression_graphs', function (Blueprint $table) {
            $table->string('key', 64)->nullable()->unique();
            $table->string('mod_id', 64)->nullable();
            $table->string('branch', 16)->default('neutral');
            $table->string('name_fr')->nullable();
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->string('key', 128)->nullable()->unique();
            $table->json('validation')->nullable();
            $table->unsignedInteger('effort')->nullable();
            $table->json('power')->nullable();
            $table->string('source', 16)->default('manual');
            $table->string('name_fr')->nullable();
            $table->text('description_fr')->nullable();
        });

        Schema::table('milestone_unlocks', function (Blueprint $table) {
            $table->string('gate_mode', 16)->nullable();
        });

        Schema::table('progressions', function (Blueprint $table) {
            $table->unsignedInteger('open_stage')->nullable();
            $table->boolean('is_public')->default(true);
        });

        Schema::create('model_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version')->unique();
            $table->string('status', 16);
            $table->unsignedSmallInteger('schema_version');
            $table->longText('document');
            $table->string('etag', 64);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('progression_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progression_id')->constrained()->cascadeOnDelete();
            $table->string('milestone_key', 128);
            $table->timestamp('completed_at');
            $table->string('completed_by', 64)->nullable();
            $table->unique(['progression_id', 'milestone_key']);
            $table->index('milestone_key');
        });

        Schema::create('progression_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progression_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('stage_number');
            $table->string('item', 191);
            $table->unsignedBigInteger('count')->default(0);
            $table->unique(['progression_id', 'stage_number', 'item']);
        });

        Schema::create('progression_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progression_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('event_id', 64)->nullable();
            $table->string('milestone_key', 128)->nullable();
            $table->unsignedInteger('stage_number')->nullable();
            $table->json('payload')->nullable();
            $table->string('actor', 64)->nullable();
            $table->unsignedInteger('snapshot_version')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();
            $table->unique(['progression_id', 'event_id']);
            $table->index(['type', 'progression_id']);
        });

        Schema::create('player_playtime', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('seconds');
            $table->unique(['user_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_playtime');
        Schema::dropIfExists('progression_events');
        Schema::dropIfExists('progression_deliveries');
        Schema::dropIfExists('progression_milestones');
        Schema::dropIfExists('model_snapshots');

        Schema::table('progressions', fn (Blueprint $table) => $table->dropColumn(['open_stage', 'is_public']));
        Schema::table('milestone_unlocks', fn (Blueprint $table) => $table->dropColumn('gate_mode'));
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn(['key', 'validation', 'effort', 'power', 'source', 'name_fr', 'description_fr']);
        });
        Schema::table('progression_graphs', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn(['key', 'mod_id', 'branch', 'name_fr']);
        });
        Schema::table('stages', fn (Blueprint $table) => $table->dropColumn(['threshold', 'module', 'name_fr', 'description_fr']));
    }
};
