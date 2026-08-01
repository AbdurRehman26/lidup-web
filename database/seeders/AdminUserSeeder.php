<?php

namespace Database\Seeders;

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.default_user.email');
        $password = config('admin.default_user.password');

        if (blank($email) || blank($password)) {
            throw new RuntimeException('Set DEFAULT_ADMIN_EMAIL and DEFAULT_ADMIN_PASSWORD before running AdminUserSeeder.');
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.default_user.name'),
                'password' => Hash::make($password),
            ],
        );
        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_admin' => true,
        ])->save();

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

        $user->forceFill([
            'subscription_package_id' => $package->id,
            'trial_plan' => $package->plan,
            'trial_started_at' => $user->trial_started_at ?? now(),
            'trial_ends_at' => null,
        ])->save();

        if ($user->tokens()->exists()) {
            $this->command?->info("Admin user {$email} has unlimited access and already has an activation key.");

            return;
        }

        $created = app(ApiKeyService::class)->create($user, 'Default admin activation key');

        $this->command?->info("Admin user {$email} has unlimited access.");
        $this->command?->warn("Activation key (also available on the dashboard): {$created['plain_text']}");
    }
}
