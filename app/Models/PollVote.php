<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{
    protected $fillable = [
        'poll_id',
        'poll_option_id',
        'user_id',
        'display_name_snapshot',
        'avatar_url_snapshot',
        'client',
        'session_id',
        'ip_address',
        'user_agent',
        'submitted_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }
}
