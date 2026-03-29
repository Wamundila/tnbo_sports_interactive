<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriviaQuestionOption extends Model
{
    protected $fillable = [
        'trivia_question_id',
        'position',
        'option_text',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TriviaQuestion::class, 'trivia_question_id');
    }
}
