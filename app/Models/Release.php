<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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

    protected static function booted(): void
    {
        static::saving(function (Release $release): void {
            if (! $release->file_path || ! $release->isDirty('file_path')) {
                return;
            }

            $disk = Storage::disk('local');

            if (! $disk->exists($release->file_path)) {
                return;
            }

            $release->file_size = $disk->size($release->file_path);
            $release->sha256 = hash_file('sha256', $disk->path($release->file_path));
        });

        static::saved(function (Release $release): void {
            if (! $release->is_current) {
                return;
            }

            static::query()
                ->whereKeyNot($release->getKey())
                ->where('is_current', true)
                ->update(['is_current' => false]);
        });
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
