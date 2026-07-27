<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

class AppActivation extends Model
{
    protected $fillable = [
        'user_id', 'personal_access_token_id', 'device_id', 'device_name', 'app_version',
        'activated_at', 'last_verified_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personalAccessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class);
    }
}
