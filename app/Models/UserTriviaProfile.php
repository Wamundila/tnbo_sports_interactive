<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTriviaProfile extends Model
{
    protected $fillable = [
        'user_id',
        'display_name_snapshot',
        'avatar_url_snapshot',
        'current_streak',
        'best_streak',
        'total_points',
        'total_correct_answers',
        'total_wrong_answers',
        'total_quizzes_played',
        'total_quizzes_completed',
        'lifetime_accuracy',
        'last_played_quiz_date',
    ];

    protected function casts(): array
    {
        return [
            'last_played_quiz_date' => 'date',
        ];
    }
}
