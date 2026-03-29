# Trivia Contract Changes - 2026-03-29

This note is the short-form contract delta for BFF and Flutter trivia integration.

Use this together with `integration_notes.md`.
The purpose of this file is only to highlight what changed on 2026-03-29 so other agents do not need to diff the larger contract document.

## Changed Endpoints

### `GET /api/v1/trivia/me/summary`

Added `rank`:

```json
{
  "user_id": "ts_123",
  "current_streak": 2,
  "best_streak": 4,
  "total_points": 27,
  "total_quizzes_played": 4,
  "total_quizzes_completed": 4,
  "lifetime_accuracy": 75,
  "rank": {
    "daily": 3,
    "weekly": 5,
    "monthly": 7,
    "all_time": 11
  },
  "today_status": {
    "played": true,
    "score_total": 9
  }
}
```

Notes:
- rank keys are `daily`, `weekly`, `monthly`, `all_time`
- any rank value may be `null`
- this shape intentionally matches `/api/v1/trivia/summary.user_summary.rank` and `/api/v1/trivia/summary.daily_trivia.rank`

### `POST /api/v1/trivia/attempts/{attempt}/submit`

Enriched `answer_review[]`:

```json
{
  "question_id": 101,
  "question_text": "Who scored the winning goal?",
  "selected_option_id": 1001,
  "selected_option_text": "Patson Daka",
  "correct_option_id": 1001,
  "correct_option_text": "Patson Daka",
  "is_correct": true,
  "explanation_text": "Explanation 1"
}
```

Notes:
- `question_text` is always present
- `selected_option_text` may be `null` if unanswered
- `correct_option_text` is always present for a valid question row

Also added `result.rank` as a convenience mirror of `leaderboard_impact`:

```json
"result": {
  "score_total": 7,
  "leaderboard_impact": {
    "daily_rank": 4,
    "weekly_rank": 7,
    "monthly_rank": 7,
    "all_time_rank": 12
  },
  "rank": {
    "daily": 4,
    "weekly": 7,
    "monthly": 7,
    "all_time": 12
  }
}
```

Notes:
- `result.rank` is redundant by design and exists for easier client rendering
- existing `leaderboard_impact` is unchanged

### `GET /api/v1/trivia/me/history`

Each history item now includes display metadata:

```json
{
  "quiz_date": "2026-03-25",
  "quiz_title": "Today's TNBO Sports Trivia",
  "completed_at": "2026-03-25T10:16:31+02:00",
  "score_total": 9,
  "correct_answers_count": 3,
  "question_count": 3,
  "streak_after": 2
}
```

Notes:
- `quiz_title` is intended for display labels on results/history screens
- `completed_at` is the submission timestamp, not only the quiz date
- `question_count` is useful for compact displays like `3/3 correct`

## Compatibility

These changes are additive.
No existing fields were removed or renamed.
Existing clients that ignore the new fields will continue to work.

## Recommended BFF / Flutter Use

- use `/me/summary.rank` for dashboard rank displays
- use submit `answer_review[].question_text` and option text fields for result review UI
- use submit `result.rank` when a direct rank object is easier than re-mapping `leaderboard_impact`
- use `history[].quiz_title` and `history[].completed_at` for richer history cards

## Source Files

- `integration_notes.md`
- `app/Http/Controllers/Api/Trivia/TriviaProfileController.php`
- `app/Services/TriviaAttemptService.php`
- `app/Services/TriviaScoringService.php`
