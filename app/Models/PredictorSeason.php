<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictorSeason extends Model
{
    protected $fillable = [
        'campaign_id',
        'name',
        'slug',
        'start_date',
        'end_date',
        'status',
        'scoring_config',
        'rules_text',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'scoring_config' => 'array',
            'is_current' => 'boolean',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PredictorCampaign::class, 'campaign_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(PredictorRound::class, 'season_id')->orderBy('opens_at');
    }
}
