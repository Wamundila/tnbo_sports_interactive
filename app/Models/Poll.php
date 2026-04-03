<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Poll extends Model
{
    protected $fillable = [
        'uuid',
        'type',
        'category',
        'title',
        'question',
        'slug',
        'description',
        'short_description',
        'status',
        'visibility',
        'open_at',
        'close_at',
        'login_required',
        'verified_account_required',
        'allow_result_view_before_vote',
        'result_visibility_mode',
        'context_type',
        'context_id',
        'sponsor_name',
        'cover_image_url',
        'banner_image_url',
        'metadata',
        'created_by_admin_id',
        'updated_by_admin_id',
        'published_by_admin_id',
        'published_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $poll): void {
            $poll->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'open_at' => 'datetime',
            'close_at' => 'datetime',
            'published_at' => 'datetime',
            'login_required' => 'boolean',
            'verified_account_required' => 'boolean',
            'allow_result_view_before_vote' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('display_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
