<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyService
{
    /**
     * @return array{key: PersonalAccessToken, plain_text: string}
     */
    public function create(User $user, string $name = 'LidUp app'): array
    {
        return DB::transaction(function () use ($user, $name) {
            $created = $user->createToken($name, [
                'activation:verify',
                'activation:deactivate',
                'tasks:complete',
            ]);

            $created->accessToken->forceFill([
                'display_token' => $created->plainTextToken,
            ])->save();

            return ['key' => $created->accessToken, 'plain_text' => $created->plainTextToken];
        });
    }

    /**
     * @return array{key: PersonalAccessToken, plain_text: string}
     */
    public function rotate(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $user->appActivations()->active()->update(['revoked_at' => now()]);
            $user->tokens()->delete();

            return $this->create($user);
        });
    }

    public function revoke(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->appActivations()->active()->update(['revoked_at' => now()]);
            $user->tokens()->delete();
        });
    }
}
