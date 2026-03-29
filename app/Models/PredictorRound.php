<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictorRound extends Model
{
    protected $fillable = [
        'season_id',
        'name',
        'round_number',
        'opens_at',
        'prediction_closes_at',
        'round_closes_at',
        'status',
        'fixture_count',
        'allow_partial_submission',
        'leaderboard_frozen_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'prediction_closes_at' => 'datetime',
            'round_closes_at' => 'datetime',
            'allow_partial_submission' => 'boolean',
            'leaderboard_frozen_at' => 'datetime',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(PredictorSeason::class, 'season_id');
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(PredictorRoundFixture::class, 'round_id')->orderBy('display_order');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PredictorRoundEntry::class, 'round_id');
    }
}
