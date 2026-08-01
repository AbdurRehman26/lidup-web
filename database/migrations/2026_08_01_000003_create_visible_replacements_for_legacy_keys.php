<?php

use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->whereHas('tokens')
            ->whereDoesntHave('tokens', fn ($query) => $query->whereNotNull('display_token'))
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    app(ApiKeyService::class)->create($user, 'Dashboard activation key');
                }
            });
    }

    public function down(): void
    {
        // Keep generated keys valid so a rollback never disconnects a user's Mac.
    }
};
