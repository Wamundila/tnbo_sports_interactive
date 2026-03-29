<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TriviaQuestion extends Model
{
    protected $fillable = [
        'trivia_quiz_id',
        'position',
        'question_text',
        'image_url',
        'explanation_text',
        'source_type',
        'source_ref',
        'difficulty',
        'sport_slug',
        'status',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(TriviaQuiz::class, 'trivia_quiz_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(TriviaQuestionOption::class)->orderBy('position');
    }
}
