<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackItem extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'rating',
        'submitter_name',
        'submitter_email',
        'status',
        'is_public',
        'admin_response',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FeedbackVote::class);
    }
}
