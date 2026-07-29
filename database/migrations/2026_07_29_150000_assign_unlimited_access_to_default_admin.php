<?php

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $package = SubscriptionPackage::query()->firstOrCreate(
            ['slug' => 'admin-unlimited'],
            [
                'name' => 'Admin Unlimited',
                'description' => 'Permanent LidUp access reserved for the default super admin.',
                'plan' => 'pro',
                'device_limit' => 3,
                'user_limit' => null,
                'duration_unit' => 'unlimited',
                'duration_value' => null,
                'is_paid' => false,
                'currency' => 'EUR',
                'is_active' => false,
                'is_visible' => false,
                'sort_order' => 999,
            ],
        );

        $email = config('admin.default_user.email') ?: 'sydabdrehman@gmail.com';
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return;
        }

        $user->forceFill([
            'is_admin' => true,
            'subscription_package_id' => $package->id,
            'trial_plan' => $package->plan,
            'trial_started_at' => $user->trial_started_at ?? now(),
            'trial_ends_at' => null,
        ])->save();

        if ($user->tokens()->exists()) {
            return;
        }

        $created = app(ApiKeyService::class)->create($user, 'Default admin activation key');

        Log::warning('Default admin activation key created during migration. Save it now; it will not be logged again.', [
            'email' => $email,
            'activation_key' => $created['plain_text'],
        ]);
    }

    public function down(): void
    {
        // Intentionally preserve administrator access and credentials on rollback.
    }
};
