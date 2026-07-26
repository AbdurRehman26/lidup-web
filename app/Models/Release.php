<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    protected $fillable = [
        'version', 'channel', 'platform', 'architecture', 'file_path',
        'file_size', 'sha256', 'minimum_os', 'release_notes',
        'is_current', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_current' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }
}
