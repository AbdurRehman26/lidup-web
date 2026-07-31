<?php

use App\Models\SubscriptionPackage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SubscriptionPackage::query()->firstOrCreate(['slug' => 'personal'], [
            'name' => 'Personal',
            'description' => 'For your everyday Mac.',
            'plan' => 'personal',
            'device_limit' => 1,
            'user_limit' => null,
            'duration_unit' => 'months',
            'duration_value' => 1,
            'is_paid' => true,
            'price' => 4,
            'currency' => 'EUR',
            'paddle_price_id' => config('plans.personal.paddle_price_id'),
            'is_active' => true,
            'is_visible' => true,
            'sort_order' => 100,
        ]);

        SubscriptionPackage::query()->firstOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'description' => 'For a multi-Mac setup.',
            'plan' => 'pro',
            'device_limit' => 3,
            'user_limit' => null,
            'duration_unit' => 'months',
            'duration_value' => 1,
            'is_paid' => true,
            'price' => 8,
            'currency' => 'EUR',
            'paddle_price_id' => config('plans.pro.paddle_price_id'),
            'is_active' => true,
            'is_visible' => true,
            'sort_order' => 110,
        ]);
    }

    public function down(): void
    {
        // Preserve package configuration and billing references on rollback.
    }
};
