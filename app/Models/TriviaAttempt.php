<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TriviaAttempt extends Model
{
    protected $fillable = [
        'trivia_quiz_id',
        'user_id',
        'display_name_snapshot',
        'avatar_url_snapshot',
        'started_at',
        'expires_at',
        'submitted_at',
        'status',
        'score_base',
        'score_bonus',
        'score_total',
        'correct_answers_count',
        'wrong_answers_count',
        'unanswered_count',
        'time_taken_seconds',
        'streak_before',
        'streak_after',
        'ranking_snapshot',
        'client_type',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'ranking_snapshot' => 'array',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(TriviaQuiz::class, 'trivia_quiz_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TriviaAttemptAnswer::class);
    }

    public function isExpired(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->expires_at->lessThanOrEqualTo($at);
    }
}
