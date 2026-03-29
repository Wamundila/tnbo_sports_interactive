<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictorPrediction extends Model
{
    protected $fillable = [
        'round_entry_id',
        'round_fixture_id',
        'predicted_home_score',
        'predicted_away_score',
        'predicted_outcome',
        'is_banker',
        'was_submitted',
        'points_awarded',
        'outcome_points',
        'exact_score_points',
        'close_score_points',
        'banker_bonus_points',
        'scoring_status',
        'scoring_notes',
        'scored_at',
    ];

    protected function casts(): array
    {
        return [
            'is_banker' => 'boolean',
            'was_submitted' => 'boolean',
            'scored_at' => 'datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(PredictorRoundEntry::class, 'round_entry_id');
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(PredictorRoundFixture::class, 'round_fixture_id');
    }
}
