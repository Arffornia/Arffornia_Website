<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The snapshot version a progression's open stage was last computed with, so a publish is applied lazily. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progressions', function (Blueprint $table) {
            $table->unsignedInteger('evaluated_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('progressions', fn (Blueprint $table) => $table->dropColumn('evaluated_version'));
    }
};
