<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TriviaQuiz extends Model
{
    protected $fillable = [
        'quiz_date',
        'title',
        'short_description',
        'trivia_banner_url',
        'status',
        'opens_at',
        'closes_at',
        'question_count_expected',
        'time_per_question_seconds',
        'points_per_correct',
        'streak_bonus_enabled',
        'sport_slug',
        'created_by_admin_id',
        'published_by_admin_id',
        'published_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quiz_date' => 'date',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'published_at' => 'datetime',
            'streak_bonus_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TriviaQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TriviaAttempt::class);
    }
}
