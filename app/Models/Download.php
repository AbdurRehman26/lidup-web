<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    protected $fillable = [
        'release_id', 'user_id', 'ip_hash', 'user_agent', 'downloaded_at',
    ];

    protected function casts(): array
    {
        return ['downloaded_at' => 'datetime'];
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
