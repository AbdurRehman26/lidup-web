<?php

namespace Database\Seeders;

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

        if ($user->tokens()->exists()) {
            $this->command?->info("Admin user {$email} is ready and already has an activation key.");

            return;
        }

        $created = app(ApiKeyService::class)->create($user, 'Default admin activation key');

        $this->command?->info("Admin user {$email} is ready.");
        $this->command?->warn("Activation key (shown once): {$created['plain_text']}");
    }
}
