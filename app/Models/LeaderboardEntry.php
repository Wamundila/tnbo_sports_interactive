<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaderboardEntry extends Model
{
    protected $fillable = [
        'board_type',
        'period_key',
        'user_id',
        'display_name_snapshot',
        'avatar_url_snapshot',
        'points',
        'quizzes_played',
        'correct_answers',
        'accuracy',
        'avg_score',
        'rank_position',
    ];
}
