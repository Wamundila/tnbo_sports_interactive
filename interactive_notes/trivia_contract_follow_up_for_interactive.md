# Trivia Contract Follow-Up For Interactive

This note is for the TNBO Interactive agent.

## Why This Follow-Up Exists

The Flutter app is now consuming the BFF trivia endpoints successfully.

The remaining gaps are not mainly transport problems. The BFF trivia routes are currently thin proxies for these Interactive endpoints:

- `GET /api/v1/trivia/me/summary`
- `GET /api/v1/trivia/me/history`
- `POST /api/v1/trivia/attempts/{attempt}/submit`

That means any missing display fields in those payloads need to be resolved at the Interactive contract level, not only in Flutter.

## Important Distinction

Some Flutter screen values already exist in the current contract, just under different names.

Current mappings already available today:

- dashboard `Attempts` -> `total_quizzes_played`
- dashboard `Accuracy` -> `lifetime_accuracy`
- result `Points` -> `result.score_total`
- history `Date` -> `quiz_date`
- history `Points` -> `score_total`

Those do not require new Interactive fields.

The items below are the real contract gaps.

## Contract Gaps To Add

### 1. Add rank to `/me/summary`

Current issue:
- the trivia dashboard wants to show a user rank under `Your Trivia Stats`
- `/me/summary` currently documents points, streak, quizzes played, and accuracy
- it does not currently document rank

Requested addition:

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
- `rank` can be an object, consistent with `/summary.daily_trivia.rank`
- null is acceptable where the user has no row
- if only one rank is preferred, `all_time` is the most useful default, but the rank object is more future-proof

### 2. Enrich submit `answer_review`

Current issue:
- Flutter results screen can show correctness booleans
- but it cannot reliably render human-friendly review rows without text fields

Current documented shape:

```json
{
  "question_id": 101,
  "selected_option_id": 1001,
  "correct_option_id": 1001,
  "is_correct": true,
  "explanation_text": "Explanation 1"
}
```

Requested richer shape:

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
- `selected_option_text` may be `null` if unanswered
- `question_text` should always be present for submitted review rows
- this avoids Flutter or BFF having to cache and reconcile old start-question payloads just to render results

### 3. Enrich history rows with title and a better timestamp

Current issue:
- the history screen can render a raw list from current data
- but it does not have a stable display title
- and `quiz_date` is weaker than a completion/submission timestamp for display

Current documented shape:

```json
{
  "quiz_date": "2026-03-25",
  "score_total": 9,
  "correct_answers_count": 3,
  "streak_after": 2
}
```

Requested richer shape:

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
- `quiz_title` prevents fallback labels like `Trivia Attempt`
- `completed_at` is better for UI than only `quiz_date`
- `question_count` is optional but useful for compact `3/3 correct` style rows

## Nice-To-Have, Not Mandatory

If convenient, you can also add a small `result.rank` convenience field on submit, but this is optional because current `leaderboard_impact` is already usable.

Example:

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

## Why This Is Better Done In Interactive

These fields belong to the gameplay domain:

- rank snapshots
- question review text
- option review text
- quiz history display metadata

If Interactive supplies them directly:
- BFF can remain a thin proxy for trivia detail routes
- Flutter gets a stable contract
- no extra reconstruction logic is needed in BFF or mobile

## Summary

Please update the Interactive contracts to add:

- `/me/summary.rank`
- `submit.answer_review[].question_text`
- `submit.answer_review[].selected_option_text`
- `submit.answer_review[].correct_option_text`
- `history.items[].quiz_title`
- `history.items[].completed_at`
- optionally `history.items[].question_count`

These are the main missing fields preventing the current Flutter trivia screens from rendering cleanly from live data.
