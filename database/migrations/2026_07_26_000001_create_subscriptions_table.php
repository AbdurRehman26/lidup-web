<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
                $table->string('provider')->nullable();
                $table->string('provider_id')->nullable()->unique();
                $table->string('plan')->default('early-access');
                $table->timestamp('renews_at')->nullable();
            });

            return;
        }

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable()->unique();
            $table->string('plan')->default('early-access');
            $table->string('status')->default('trialing')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['provider', 'provider_id', 'plan', 'renews_at']);
        });
    }
};
