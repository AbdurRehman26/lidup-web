<?php

use App\Models\SubscriptionPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->string('billing_interval')->default('month')->after('currency');
        });

        SubscriptionPackage::query()->firstOrCreate(['slug' => 'personal-yearly'], [
            'name' => 'Personal Yearly',
            'description' => 'Two months free for your everyday Mac.',
            'plan' => 'personal',
            'device_limit' => 1,
            'user_limit' => null,
            'duration_unit' => 'months',
            'duration_value' => 12,
            'is_paid' => true,
            'price' => 40,
            'currency' => 'EUR',
            'billing_interval' => 'year',
            'paddle_price_id' => config('plans.personal_yearly.paddle_price_id'),
            'is_active' => true,
            'is_visible' => true,
            'sort_order' => 101,
        ]);

        SubscriptionPackage::query()->firstOrCreate(['slug' => 'pro-yearly'], [
            'name' => 'Pro Yearly',
            'description' => 'Two months free for your multi-Mac setup.',
            'plan' => 'pro',
            'device_limit' => 3,
            'user_limit' => null,
            'duration_unit' => 'months',
            'duration_value' => 12,
            'is_paid' => true,
            'price' => 80,
            'currency' => 'EUR',
            'billing_interval' => 'year',
            'paddle_price_id' => config('plans.pro_yearly.paddle_price_id'),
            'is_active' => true,
            'is_visible' => true,
            'sort_order' => 111,
        ]);
    }

    public function down(): void
    {
        SubscriptionPackage::query()->whereIn('slug', ['personal-yearly', 'pro-yearly'])->delete();

        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn('billing_interval');
        });
    }
};
