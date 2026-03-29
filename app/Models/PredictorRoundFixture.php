<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictorRoundFixture extends Model
{
    protected $fillable = [
        'round_id',
        'source_fixture_id',
        'competition_id',
        'competition_name_snapshot',
        'home_team_id',
        'away_team_id',
        'home_team_name_snapshot',
        'away_team_name_snapshot',
        'home_team_logo_url',
        'away_team_logo_url',
        'kickoff_at',
        'display_order',
        'result_status',
        'actual_home_score',
        'actual_away_score',
        'result_entered_at',
        'result_source',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'kickoff_at' => 'datetime',
            'result_entered_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(PredictorRound::class, 'round_id');
    }
}
