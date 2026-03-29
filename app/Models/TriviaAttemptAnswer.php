<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriviaAttemptAnswer extends Model
{
    protected $fillable = [
        'trivia_attempt_id',
        'trivia_question_id',
        'trivia_question_option_id',
        'is_correct',
        'answered_at',
        'response_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TriviaAttempt::class, 'trivia_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TriviaQuestion::class, 'trivia_question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(TriviaQuestionOption::class, 'trivia_question_option_id');
    }
}
