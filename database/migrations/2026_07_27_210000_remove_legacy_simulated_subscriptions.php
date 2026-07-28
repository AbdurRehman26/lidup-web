<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscriptions') || ! Schema::hasColumn('subscriptions', 'paddle_id')) {
            return;
        }

        DB::table('subscriptions')
            ->whereNull('paddle_id')
            ->delete();
    }

    public function down(): void
    {
        // Simulated subscriptions were never backed by a Paddle purchase.
    }
};
