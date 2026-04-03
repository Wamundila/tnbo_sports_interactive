<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    protected $fillable = [
        'poll_id',
        'title',
        'subtitle',
        'description',
        'image_url',
        'video_url',
        'thumbnail_url',
        'badge_text',
        'stats_summary',
        'entity_type',
        'entity_id',
        'display_order',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'poll_option_id');
    }
}
