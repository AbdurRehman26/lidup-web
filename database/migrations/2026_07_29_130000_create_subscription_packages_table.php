<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('plan')->default('personal');
            $table->unsignedInteger('device_limit')->default(1);
            $table->unsignedInteger('user_limit')->nullable();
            $table->string('duration_unit')->default('days');
            $table->unsignedInteger('duration_value')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('paddle_price_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subscription_package_id')
                ->nullable()
                ->after('trial_cohort_position')
                ->constrained()
                ->nullOnDelete();
        });

        DB::table('subscription_packages')->insert([
            [
                'name' => 'Tier 1',
                'slug' => 'tier-1',
                'description' => 'Early access for the first 100 LidUp users.',
                'plan' => 'personal',
                'device_limit' => 1,
                'user_limit' => 100,
                'duration_unit' => 'days',
                'duration_value' => 14,
                'is_paid' => false,
                'currency' => 'EUR',
                'is_active' => true,
                'is_visible' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tier 2',
                'slug' => 'tier-2',
                'description' => 'Extended access for the next 500 LidUp users.',
                'plan' => 'personal',
                'device_limit' => 1,
                'user_limit' => 500,
                'duration_unit' => 'months',
                'duration_value' => 1,
                'is_paid' => false,
                'currency' => 'EUR',
                'is_active' => true,
                'is_visible' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tier 3',
                'slug' => 'tier-3',
                'description' => 'Unlimited-duration access for the final early-access cohort.',
                'plan' => 'personal',
                'device_limit' => 1,
                'user_limit' => 100,
                'duration_unit' => 'unlimited',
                'duration_value' => null,
                'is_paid' => false,
                'currency' => 'EUR',
                'is_active' => true,
                'is_visible' => false,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_package_id');
        });

        Schema::dropIfExists('subscription_packages');
    }
};
