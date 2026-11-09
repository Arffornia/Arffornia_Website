<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The enum's CHECK constraint survives ->change() on Postgres; the allowed statuses now live in PendingReward.
        if (DB::getDriverName() === 'pgsql') {
            $checks = DB::select(
                "SELECT conname FROM pg_constraint WHERE conrelid = 'pending_rewards'::regclass AND contype = 'c' AND pg_get_constraintdef(oid) LIKE '%status%'"
            );
            foreach ($checks as $check) {
                DB::statement('ALTER TABLE pending_rewards DROP CONSTRAINT "' . str_replace('"', '""', $check->conname) . '"');
            }
        }

        Schema::table('pending_rewards', function (Blueprint $table) {
            $table->string('status', 16)->default('pending')->change();
            $table->json('commands')->nullable()->change();
            $table->json('contents')->nullable();
            $table->string('source', 16)->default('shop');
            $table->string('claim_key', 64)->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->string('hold_reason', 255)->nullable();
            $table->index(['user_id', 'status']);
        });

        DB::table('pending_rewards')->whereNull('shop_item_id')->update(['source' => 'vote']);

        Schema::table('shop_items', function (Blueprint $table) {
            $table->json('contents')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->date('last_streak_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_streak_date');
        });

        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropColumn('contents');
        });

        DB::table('pending_rewards')->where('status', 'on_hold')->update(['status' => 'failed']);
        DB::table('pending_rewards')->where('status', 'pending')->where('source', 'streak')->update(['status' => 'failed']);

        Schema::table('pending_rewards', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn(['contents', 'source', 'claim_key', 'claimed_at', 'hold_reason']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pending_rewards ADD CONSTRAINT pending_rewards_status_check CHECK (status IN ('pending', 'claimed', 'failed'))");
        }
    }
};
