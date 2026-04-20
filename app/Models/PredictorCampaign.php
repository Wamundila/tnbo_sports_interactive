<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PredictorCampaign extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'display_name',
        'sponsor_name',
        'description',
        'banner_image_url',
        'scope_type',
        'default_fixture_count',
        'banker_enabled',
        'status',
        'visibility',
        'starts_at',
        'ends_at',
        'metadata',
        'created_by_admin_id',
        'updated_by_admin_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $campaign): void {
            $campaign->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'banker_enabled' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(PredictorSeason::class, 'campaign_id');
    }

    public function competitions(): HasMany
    {
        return $this->hasMany(PredictorCampaignCompetition::class, 'campaign_id')->orderBy('sort_order');
    }
}
