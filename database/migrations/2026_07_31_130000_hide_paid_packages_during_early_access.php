<?php

use App\Models\SubscriptionPackage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SubscriptionPackage::query()
            ->where('is_paid', true)
            ->update(['is_visible' => false]);
    }

    public function down(): void
    {
        SubscriptionPackage::query()
            ->whereIn('slug', ['personal', 'pro'])
            ->update(['is_visible' => true]);
    }
};
