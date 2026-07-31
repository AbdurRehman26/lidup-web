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
            $table->decimal('original_price', 10, 2)->nullable()->after('price');
        });

        SubscriptionPackage::query()->where('is_paid', true)->update([
            'is_active' => false,
            'is_visible' => false,
        ]);

        SubscriptionPackage::query()->where('slug', 'personal')->update([
            'name' => 'Personal Lifetime',
            'description' => 'Pay once and use LidUp forever on one Mac.',
            'duration_unit' => 'lifetime',
            'duration_value' => null,
            'price' => 39,
            'original_price' => 49,
            'billing_interval' => 'one_time',
            'paddle_price_id' => config('plans.personal.paddle_price_id'),
            'is_active' => true,
            'is_visible' => true,
        ]);

        SubscriptionPackage::query()->where('slug', 'pro')->update([
            'name' => 'Pro Lifetime',
            'description' => 'Pay once and use LidUp forever on up to three Macs.',
            'duration_unit' => 'lifetime',
            'duration_value' => null,
            'price' => 79,
            'original_price' => 99,
            'billing_interval' => 'one_time',
            'paddle_price_id' => config('plans.pro.paddle_price_id'),
            'is_active' => true,
            'is_visible' => true,
        ]);
    }

    public function down(): void
    {
        SubscriptionPackage::query()->where('slug', 'personal')->update([
            'name' => 'Personal',
            'description' => 'For your everyday Mac.',
            'duration_unit' => 'months',
            'duration_value' => 1,
            'price' => 4,
            'billing_interval' => 'month',
        ]);

        SubscriptionPackage::query()->where('slug', 'pro')->update([
            'name' => 'Pro',
            'description' => 'For a multi-Mac setup.',
            'duration_unit' => 'months',
            'duration_value' => 1,
            'price' => 8,
            'billing_interval' => 'month',
        ]);

        SubscriptionPackage::query()->where('is_paid', true)->update([
            'is_active' => true,
            'is_visible' => true,
        ]);

        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn('original_price');
        });
    }
};
