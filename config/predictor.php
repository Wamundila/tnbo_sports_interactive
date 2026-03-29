<?php

return [
    'leaderboard_default_limit' => (int) env('PREDICTOR_LEADERBOARD_DEFAULT_LIMIT', 50),
    'leaderboard_preview_limit' => (int) env('PREDICTOR_LEADERBOARD_PREVIEW_LIMIT', 5),
    'default_scoring' => [
        'outcome_points' => (float) env('PREDICTOR_OUTCOME_POINTS', 3),
        'exact_score_points' => (float) env('PREDICTOR_EXACT_SCORE_POINTS', 5),
        'close_score_points' => (float) env('PREDICTOR_CLOSE_SCORE_POINTS', 1.5),
        'banker_enabled' => (bool) env('PREDICTOR_BANKER_ENABLED', true),
        'banker_multiplier' => (float) env('PREDICTOR_BANKER_MULTIPLIER', 2),
    ],
];
