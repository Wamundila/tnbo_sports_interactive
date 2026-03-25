# 05. API Contract

## API path style

Interactive APIs are intended to be called by BFF, not directly by the Flutter app.

Suggested base path inside Interactive:

```text
/api/v1/trivia
```

BFF can expose its own client-safe routes and internally map them.

## Shared rules

- user must be authenticated
- user must be verified
- only one attempt per quiz per user
- all scoring is calculated server-side
- client must never send score values

## 1. Get today's quiz summary

`GET /api/v1/trivia/today`

### Response

```json
{
  "date": "2026-03-25",
  "available": true,
  "quiz": {
    "id": 101,
    "title": "Today's TNBO Sports Trivia",
    "description": "3 questions • 90 seconds total potential • 9 base points",
    "opens_at": "2026-03-25T06:00:00Z",
    "closes_at": "2026-03-25T21:00:00Z",
    "question_count": 3,
    "time_per_question_seconds": 30,
    "points_per_correct": 3,
    "already_played": false,
    "requires_verified_account": true
  }
}
```

## 2. Start today's quiz attempt

`POST /api/v1/trivia/today/start`

### Request

```json
{
  "client": "flutter_android"
}
```

### Response

```json
{
  "attempt_id": 9001,
  "quiz": {
    "id": 101,
    "date": "2026-03-25",
    "question_count": 3,
    "time_per_question_seconds": 30
  },
  "questions": [
    {
      "id": 501,
      "position": 1,
      "question_text": "Who scored the winning goal in yesterday's match?",
      "image_url": null,
      "options": [
        {"id": 7001, "position": 1, "option_text": "Player A"},
        {"id": 7002, "position": 2, "option_text": "Player B"},
        {"id": 7003, "position": 3, "option_text": "Player C"}
      ]
    }
  ]
}
```

### Start rules

- return existing in-progress attempt if appropriate
- reject if quiz closed
- reject if user not verified
- reject if already submitted

## 3. Submit quiz answers

`POST /api/v1/trivia/attempts/{attempt_id}/submit`

### Request

```json
{
  "answers": [
    {
      "question_id": 501,
      "option_id": 7002,
      "response_time_ms": 14000
    },
    {
      "question_id": 502,
      "option_id": 7011,
      "response_time_ms": 19000
    },
    {
      "question_id": 503,
      "option_id": 7020,
      "response_time_ms": 9000
    }
  ]
}
```

### Response

```json
{
  "attempt_id": 9001,
  "result": {
    "score_base": 6,
    "score_bonus": 3,
    "score_total": 9,
    "correct_answers_count": 2,
    "wrong_answers_count": 1,
    "unanswered_count": 0,
    "streak_before": 2,
    "streak_after": 3,
    "new_badges": [],
    "leaderboard_impact": {
      "daily_rank": 14,
      "weekly_rank": 31,
      "monthly_rank": 48,
      "all_time_rank": 120
    }
  },
  "answer_review": [
    {
      "question_id": 501,
      "selected_option_id": 7002,
      "correct_option_id": 7002,
      "is_correct": true,
      "explanation_text": "He scored in the 78th minute."
    }
  ]
}
```

## 4. Get current user trivia summary

`GET /api/v1/trivia/me/summary`

### Response

```json
{
  "user_id": "usr_123",
  "current_streak": 3,
  "best_streak": 7,
  "total_points": 84,
  "total_quizzes_played": 14,
  "total_quizzes_completed": 14,
  "lifetime_accuracy": 76.19,
  "today_status": {
    "played": true,
    "score_total": 9
  }
}
```

## 5. Get leaderboard

`GET /api/v1/trivia/leaderboards?board_type=daily&period_key=2026-03-25`

### Response

```json
{
  "board_type": "daily",
  "period_key": "2026-03-25",
  "entries": [
    {
      "rank": 1,
      "user": {
        "user_id": "usr_11",
        "display_name": "John D.",
        "avatar_url": null
      },
      "points": 12,
      "accuracy": 100,
      "quizzes_played": 1
    }
  ],
  "current_user": {
    "rank": 14,
    "points": 9
  }
}
```

## 6. Get quiz history for current user

`GET /api/v1/trivia/me/history`

### Response

```json
{
  "items": [
    {
      "quiz_date": "2026-03-24",
      "score_total": 6,
      "correct_answers_count": 2,
      "streak_after": 2
    }
  ]
}
```

## 7. Admin APIs

Suggested admin routes:

- `GET /admin/trivia/quizzes`
- `POST /admin/trivia/quizzes`
- `PUT /admin/trivia/quizzes/{id}`
- `POST /admin/trivia/quizzes/{id}/publish`
- `POST /admin/trivia/quizzes/{id}/close`
- `POST /admin/trivia/quizzes/{id}/duplicate`

## Validation notes

On submit:

- attempt must belong to current user
- attempt must still be submittable
- options must belong to the referenced question
- questions must belong to the referenced quiz
- duplicate answers in payload rejected
- scoring recomputed on server

## Error examples

### Not verified

```json
{
  "message": "Verified TNBO Sports account required to participate.",
  "code": "TRIVIA_VERIFICATION_REQUIRED"
}
```

### Already submitted

```json
{
  "message": "You have already completed today's trivia.",
  "code": "TRIVIA_ALREADY_PLAYED"
}
```
