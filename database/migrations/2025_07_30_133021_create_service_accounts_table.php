<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('A human-readable name for the service account.');
            $table->uuid('client_id')->unique()->comment('The public identifier for the service account.');
            $table->string('client_secret')->comment('The hashed secret for the service account.');
            $table->text('roles')->comment('Comma-separated list of roles/permissions.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_accounts');
    }
};
