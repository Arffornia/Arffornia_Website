<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 019: the history of an editor draft. `edit` rows hold the entity changes undo and redo replay;
 * every row, undo and redo included, is the audit log (who changed what, when, in which draft).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progression_draft_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained('progression_drafts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16);
            $table->string('action', 32)->nullable();
            $table->string('subject', 191)->nullable();
            $table->longText('changes')->nullable();
            $table->string('state', 16)->nullable();
            $table->foreignId('target_id')->nullable();
            $table->unsignedInteger('revision');
            $table->timestamp('created_at')->nullable();
            $table->index(['draft_id', 'type', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progression_draft_entries');
    }
};
