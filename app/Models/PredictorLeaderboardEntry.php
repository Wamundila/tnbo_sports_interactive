<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictorLeaderboardEntry extends Model
{
    protected $fillable = [
        'leaderboard_type',
        'campaign_id',
        'season_id',
        'round_id',
        'leaderboard_period_key',
        'user_id',
        'display_name_snapshot',
        'avatar_url_snapshot',
        'rank',
        'points_total',
        'rounds_played',
        'correct_outcomes_count',
        'exact_scores_count',
        'close_score_count',
        'accuracy_percentage',
        'metadata',
        'refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'refreshed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PredictorCampaign::class, 'campaign_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(PredictorSeason::class, 'season_id');
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(PredictorRound::class, 'round_id');
    }
}
