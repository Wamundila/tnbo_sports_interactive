<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictorRoundEntry extends Model
{
    protected $fillable = [
        'round_id',
        'campaign_id',
        'season_id',
        'user_id',
        'display_name_snapshot',
        'avatar_url_snapshot',
        'entry_status',
        'submitted_at',
        'last_edited_at',
        'total_points',
        'correct_outcomes_count',
        'exact_scores_count',
        'close_score_count',
        'banker_fixture_id',
        'banker_multiplier',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'last_edited_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(PredictorRound::class, 'round_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(PredictorSeason::class, 'season_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PredictorCampaign::class, 'campaign_id');
    }

    public function bankerFixture(): BelongsTo
    {
        return $this->belongsTo(PredictorRoundFixture::class, 'banker_fixture_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(PredictorPrediction::class, 'round_entry_id');
    }
}
