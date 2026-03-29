<?php

return [
    'attempt_grace_seconds' => (int) env('TRIVIA_ATTEMPT_GRACE_SECONDS', 5),
    'leaderboard_default_limit' => (int) env('TRIVIA_LEADERBOARD_DEFAULT_LIMIT', 50),
    'leaderboard_preview_limit' => (int) env('TRIVIA_LEADERBOARD_PREVIEW_LIMIT', 5),
];
