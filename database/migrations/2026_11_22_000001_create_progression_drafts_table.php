<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 019: the admin editor works on a draft document of its own, written to the editable tables
 * only when it is published, so nothing the editor does is live before a publish.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progression_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('status', 16)->default('open');
            $table->longText('document');
            $table->unsignedInteger('revision')->default(0);
            $table->unsignedInteger('base_version')->nullable();
            $table->string('base_hash', 64);
            $table->unsignedInteger('published_version')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progression_drafts');
    }
};
