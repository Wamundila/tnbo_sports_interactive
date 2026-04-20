# TNBO Trivia API Integration Notes

Current integration notes for the protected TNBO Sports trivia user APIs.

Short-form delta for the latest contract additions:
- `interactive_notes/trivia_contract_changes_2026-03-29.md`

## Scope

These APIs are intended for internal TNBO integration.

Important:
- Flutter should not call this service directly.
- Expected request path is `Flutter -> BFF -> Interactive`.
- End-user identity comes from AuthBox JWTs.
- Trusted user IDs are `ts_*` IDs from JWT `sub`.
- `start` and `submit` require a verified TNBO Sports account.

## Base Path

```text
/api/v1/trivia
```

## Required Headers

All user API requests require:

```http
Authorization: Bearer <authbox_jwt>
```

If `INTERACTIVE_SERVICE_KEY` is configured, also send:

```http
X-TNBO-Service-Key: <shared_internal_service_key>
```

## Auth / Verification Rules

### JWT
The service verifies the AuthBox JWT locally.

Current checks:
- bearer token must be present
- signature must be valid
- algorithm must match configured algorithm, normally `RS256`
- `sub` must match configured subject pattern, default `^ts_\d+$`
- `exp` and `nbf` are enforced with configured clock skew
- `iss` is enforced if configured
- `aud` is enforced if configured

### Verified-account enforcement
These endpoints require a verified account from AuthBox profile data:
- `POST /today/start`
- `POST /attempts/{attempt}/submit`

Verification is resolved from AuthBox profile lookup, not a client-sent boolean.

The current AuthBox integration sends:
- `Authorization: Bearer <authbox_jwt>`
- `X-API-Key: <authbox_api_key>`

### `/today` state shaping
`GET /today` returns a product-facing `state` so BFF and TNBO Sports surfaces do not need to infer everything from mixed booleans and error paths.

Current possible values:
- `available`
- `in_progress`
- `already_played`
- `not_open`
- `closed`
- `no_quiz`
- `verification_required`

Notes:
- `available` still reflects quiz-level availability, not final CTA state by itself
- `state` is the main field BFF should use for UI mapping
- `verification_required` is best-effort from current AuthBox profile lookup on `/today`
- if profile lookup cannot be resolved during `/today`, the service keeps the endpoint resilient and may fall back to `available`

## Common Error Envelope

Protected API errors use this JSON shape:

```json
{
  "message": "Human readable message.",
  "code": "MACHINE_READABLE_CODE",
  "errors": {
    "optional": ["validation details when applicable"]
  }
}
```

`errors` is only present when there is structured validation/context data.

## Recent Contract Additions

The following fields were added to support current TNBO Sports Flutter/BFF trivia surfaces:

- `GET /api/v1/trivia/me/summary` now includes `rank`:

```json
"rank": {
  "daily": 3,
  "weekly": 5,
  "monthly": 7,
  "all_time": 11
}
```

- `POST /api/v1/trivia/attempts/{attempt}/submit` now includes richer review rows:

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

- `POST /api/v1/trivia/attempts/{attempt}/submit` also includes `result.rank` as a convenience mirror of `leaderboard_impact`.

- Admin-managed trivia banner uploads now surface as `trivia_banner_url` on `/today`, `/summary.daily_trivia`, and `/today/start.quiz`.

- `GET /api/v1/trivia/me/history` rows now include:

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

These additions are live in the current contract even where older example blocks below are less detailed.
## Current User APIs

### 1. Get today's trivia

```http
GET /api/v1/trivia/today
```

Purpose:
- tells the BFF whether today's quiz is available at the quiz level
- returns product-facing current user trivia state
- returns quiz metadata without exposing question content
- returns current attempt summary when the user has an active in-progress attempt

Sample request:
```bash
curl -X GET "http://localhost:8000/api/v1/trivia/today" \
  -H "Authorization: Bearer <authbox_jwt>" \
  -H "X-TNBO-Service-Key: <service_key>"
```

Sample success response when no quiz exists for today:
```json
{
  "date": "2026-03-25",
  "available": false,
  "state": "no_quiz",
  "current_attempt": null,
  "quiz": null
}
```

Sample success response when quiz is open and playable:
```json
{
  "date": "2026-03-25",
  "available": true,
  "state": "available",
  "current_attempt": null,
  "quiz": {
    "id": 1,
    "title": "Today's TNBO Sports Trivia",
    "description": "3 questions - 90 seconds total potential - 9 base points",
    "trivia_banner_url": "/uploads/trivia/banners/20260420100000-example.jpg",
    "opens_at": "2026-03-25T09:00:00+02:00",
    "closes_at": "2026-03-25T21:00:00+02:00",
    "question_count": 3,
    "time_per_question_seconds": 30,
    "points_per_correct": 3,
    "already_played": false,
    "requires_verified_account": true
  }
}
```

Sample success response when there is an active attempt:
```json
{
  "date": "2026-03-25",
  "available": true,
  "state": "in_progress",
  "current_attempt": {
    "attempt_id": 12,
    "started_at": "2026-03-25T10:15:00+02:00",
    "expires_at": "2026-03-25T10:16:35+02:00"
  },
  "quiz": {
    "id": 1,
    "title": "Today's TNBO Sports Trivia",
    "description": "3 questions - 90 seconds total potential - 9 base points",
    "trivia_banner_url": "/uploads/trivia/banners/20260420100000-example.jpg",
    "opens_at": "2026-03-25T09:00:00+02:00",
    "closes_at": "2026-03-25T21:00:00+02:00",
    "question_count": 3,
    "time_per_question_seconds": 30,
    "points_per_correct": 3,
    "already_played": false,
    "requires_verified_account": true
  }
}
```

Other likely states:
- `already_played`: user already submitted today's quiz
- `not_open`: quiz exists for today but is not open yet or is not yet publish-ready
- `closed`: quiz exists for today but is no longer playable
- `verification_required`: quiz is available but current profile indicates the account is not verified

Notes:
- `quiz` metadata is returned even when state is `not_open` or `closed`, as long as a quiz exists for today
- use `state` as the primary UI signal
- use `current_attempt.expires_at` for resume/countdown behavior

### 2. Get trivia surface summary

```http
GET /api/v1/trivia/summary
```

Purpose:
- returns a single trivia surface payload for BFF composition
- reduces round trips for Home and Games page trivia blocks
- combines the daily trivia block, user summary, and leaderboard previews

Sample request:
```bash
curl -X GET "http://localhost:8000/api/v1/trivia/summary" \
  -H "Authorization: Bearer <authbox_jwt>" \
  -H "X-TNBO-Service-Key: <service_key>"
```

Sample success response after a completed play:
```json
{
  "date": "2026-03-29",
  "daily_trivia": {
    "title": "Today's TNBO Sports Trivia",
    "short_description": "3 questions - 90 seconds total potential - 9 base points",
    "trivia_banner_url": "/uploads/trivia/banners/20260420100000-example.jpg",
    "state": "already_played",
    "available": true,
    "requires_verified_account": true,
    "opens_at": "2026-03-29T09:00:00+02:00",
    "closes_at": "2026-03-29T21:00:00+02:00",
    "current_attempt": null,
    "today_score_total": 9,
    "rank": {
      "daily": 1,
      "weekly": null,
      "monthly": null,
      "all_time": null
    },
    "points": {
      "today": 9,
      "total": 9
    },
    "streak": {
      "current": 1,
      "best": 1
    }
  },
  "user_summary": {
    "user_id": "ts_123",
    "current_streak": 1,
    "best_streak": 1,
    "total_points": 9,
    "total_quizzes_played": 1,
    "total_quizzes_completed": 1,
    "lifetime_accuracy": 100,
    "today_status": {
      "played": true,
      "score_total": 9
    }
  },
  "leaderboard_previews": {
    "daily": {
      "board_type": "daily",
      "period_key": "2026-03-29",
      "entries": [
        {
          "rank": 1,
          "user": {
            "user_id": "ts_123",
            "display_name": "John D.",
            "avatar_url": "https://cdn.test/avatar.png"
          },
          "points": 9,
          "accuracy": 100,
          "quizzes_played": 1
        }
      ]
    },
    "weekly": {
      "board_type": "weekly",
      "period_key": "2026-W13",
      "entries": []
    },
    "monthly": {
      "board_type": "monthly",
      "period_key": "2026-03",
      "entries": []
    },
    "all_time": {
      "board_type": "all_time",
      "period_key": "all",
      "entries": []
    }
  }
}
```

Notes:
- this endpoint is protected the same way as the other trivia user APIs
- `daily_trivia.state` uses the same meanings as `GET /today`
- `current_attempt` is only present when state is `in_progress`
- leaderboard previews default to the current periods and use the server preview limit, currently `5`
- rank values return `null` when the user has no row and no stored attempt snapshot for the relevant board

### 3. Start today's trivia attempt

```http
POST /api/v1/trivia/today/start
```

Request body:
```json
{
  "client": "flutter_android"
}
```

Request fields:
- `client`: optional string for client/source labeling, max 50 chars

Purpose:
- creates a new in-progress attempt for the authenticated `ts_*` user
- or returns the existing in-progress attempt if it has not expired yet
- returns the playable question payload

Sample request:
```bash
curl -X POST "http://localhost:8000/api/v1/trivia/today/start" \
  -H "Authorization: Bearer <authbox_jwt>" \
  -H "X-TNBO-Service-Key: <service_key>" \
  -H "Content-Type: application/json" \
  -d '{"client":"flutter_android"}'
```

Sample success response:
```json
{
  "attempt_id": 12,
  "status": "in_progress",
  "started_at": "2026-03-25T10:15:00+02:00",
  "expires_at": "2026-03-25T10:16:35+02:00",
  "already_played": false,
  "requires_verified_account": true,
  "quiz": {
    "id": 1,
    "date": "2026-03-25",
    "trivia_banner_url": "/uploads/trivia/banners/20260420100000-example.jpg",
    "question_count": 3,
    "time_per_question_seconds": 30
  },
  "questions": [
    {
      "id": 101,
      "position": 1,
      "question_text": "Question 1",
      "image_url": null,
      "options": [
        {"id": 1001, "position": 1, "option_text": "Option 1.1"},
        {"id": 1002, "position": 2, "option_text": "Option 1.2"},
        {"id": 1003, "position": 3, "option_text": "Option 1.3"}
      ]
    }
  ]
}
```

Notes:
- this endpoint requires a verified account
- if an in-progress attempt already exists and is still valid, the same attempt is returned
- if an old in-progress attempt has already expired, the call fails with `TRIVIA_ATTEMPT_EXPIRED`
- if the user has already submitted today's quiz, the call fails with `TRIVIA_ALREADY_PLAYED`

### 4. Submit trivia attempt

```http
POST /api/v1/trivia/attempts/{attempt}/submit
```

Path params:
- `attempt`: integer attempt id returned by `start`

Request body:
```json
{
  "answers": [
    {"question_id": 101, "option_id": 1001, "response_time_ms": 1200},
    {"question_id": 102, "option_id": 1005, "response_time_ms": 900},
    {"question_id": 103, "option_id": 1007, "response_time_ms": 1100}
  ]
}
```

Request fields:
- `answers`: required array, minimum 1 row
- `answers[].question_id`: required integer, distinct per request
- `answers[].option_id`: nullable integer
- `answers[].response_time_ms`: nullable integer, minimum 0

Notes:
- `option_id` can be `null` to represent unanswered
- every `question_id` and `option_id` must belong to the quiz/question for that attempt
- this endpoint requires a verified account

Sample request:
```bash
curl -X POST "http://localhost:8000/api/v1/trivia/attempts/12/submit" \
  -H "Authorization: Bearer <authbox_jwt>" \
  -H "X-TNBO-Service-Key: <service_key>" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": [
      {"question_id":101,"option_id":1001,"response_time_ms":1200},
      {"question_id":102,"option_id":1005,"response_time_ms":900},
      {"question_id":103,"option_id":1007,"response_time_ms":1100}
    ]
  }'
```

Sample success response:
```json
{
  "attempt_id": 12,
  "result": {
    "score_base": 6,
    "score_bonus": 1,
    "score_total": 7,
    "correct_answers_count": 2,
    "wrong_answers_count": 1,
    "unanswered_count": 0,
    "streak_before": 0,
    "streak_after": 1,
    "new_badges": [],
    "leaderboard_impact": {
      "daily_rank": 4,
      "weekly_rank": 7,
      "monthly_rank": 7,
      "all_time_rank": 12
    }
  },
  "answer_review": [
    {
      "question_id": 101,
      "selected_option_id": 1001,
      "correct_option_id": 1001,
      "is_correct": true,
      "explanation_text": "Explanation 1"
    }
  ]
}
```

Notes:
- this finalizes the attempt and updates profile + leaderboard state
- `new_badges` currently always returns an empty array
- `leaderboard_impact` contains the user's latest rank on each board after this submission

### 5. Get current user summary

```http
GET /api/v1/trivia/me/summary
```

Purpose:
- returns aggregate trivia profile data for the authenticated user

Sample request:
```bash
curl -X GET "http://localhost:8000/api/v1/trivia/me/summary" \
  -H "Authorization: Bearer <authbox_jwt>" \
  -H "X-TNBO-Service-Key: <service_key>"
```

Sample success response:
```json
{
  "user_id": "ts_123",
  "current_streak": 2,
  "best_streak": 4,
  "total_points": 27,
  "total_quizzes_played": 4,
  "total_quizzes_completed": 4,
  "lifetime_accuracy": 75,
  "today_status": {
    "played": true,
    "score_total": 9
  }
}
```

Notes:
- if the user has no profile yet, numeric fields return `0`
- `today_status.played` only becomes `true` after a submitted attempt

### 6. Get current user history

```http
GET /api/v1/trivia/me/history
```

Purpose:
- returns up to 20 submitted attempts for the authenticated user

Sample request:
```bash
curl -X GET "http://localhost:8000/api/v1/trivia/me/history" \
  -H "Authorization: Bearer <authbox_jwt>" \
  -H "X-TNBO-Service-Key: <service_key>"
```

Sample success response:
```json
{
  "items": [
    {
      "quiz_date": "2026-03-25",
      "score_total": 9,
      "correct_answers_count": 3,
      "streak_after": 2
    },
    {
      "quiz_date": "2026-03-24",
      "score_total": 6,
      "correct_answers_count": 2,
      "streak_after": 1
    }
  ]
}
```

### 7. Get leaderboard

```http
GET /api/v1/trivia/leaderboards
```

Query params:
- `board_type`: optional, one of `daily`, `weekly`, `monthly`, `all_time`, default `daily`
- `period_key`: optional, defaults to the current period for the selected board type
- `limit`: optional integer, minimum `1`, maximum `200`

Current default period key formats:
- `daily`: `YYYY-MM-DD`, example `2026-03-25`
- `weekly`: `YYYY-Www`, example `2026-W13`
- `monthly`: `YYYY-MM`, example `2026-03`
- `all_time`: `all`

Sample request:
```bash
curl -X GET "http://localhost:8000/api/v1/trivia/leaderboards?board_type=daily&period_key=2026-03-25&limit=5" \
  -H "Authorization: Bearer <authbox_jwt>" \
  -H "X-TNBO-Service-Key: <service_key>"
```

Sample success response:
```json
{
  "board_type": "daily",
  "period_key": "2026-03-25",
  "limit": 5,
  "entries": [
    {
      "rank": 1,
      "user": {
        "user_id": "ts_123",
        "display_name": "John D.",
        "avatar_url": "https://cdn.test/avatar.png"
      },
      "points": 9,
      "accuracy": 100,
      "quizzes_played": 1
    },
    {
      "rank": 2,
      "user": {
        "user_id": "ts_456",
        "display_name": "Mary K.",
        "avatar_url": null
      },
      "points": 6,
      "accuracy": 66.67,
      "quizzes_played": 1
    }
  ],
  "current_user": {
    "rank": 1,
    "points": 9
  }
}
```

Notes:
- result limit is controlled by server config and/or query param
- invalid `limit` values fail request validation
- `current_user` is `null` if the authenticated user has no row on that board

## Current Error Codes to Handle

### Authentication / service protection
- `AUTH_TOKEN_MISSING`
- `AUTH_TOKEN_INVALID`
- `AUTH_TOKEN_EXPIRED`
- `SERVICE_UNAUTHORIZED`
- `AUTH_USER_MISSING`
- `AUTHBOX_USER_MISMATCH`
- `AUTHBOX_PROFILE_UNAVAILABLE`
- `AUTHBOX_PROFILE_INVALID`
- `AUTH_CONFIGURATION_ERROR`
- `AUTHBOX_CONFIGURATION_ERROR`

### Trivia state / business rules
- `TRIVIA_VERIFICATION_REQUIRED`
- `TRIVIA_NOT_OPEN`
- `TRIVIA_CLOSED`
- `TRIVIA_ALREADY_PLAYED`
- `TRIVIA_ATTEMPT_EXPIRED`
- `TRIVIA_ATTEMPT_NOT_FOUND`
- `TRIVIA_INVALID_ANSWER_PAYLOAD`
- `TRIVIA_INVALID_CONFIGURATION`

### Generic validation / HTTP wrappers
- `VALIDATION_ERROR`
- `HTTP_ERROR`

## Recommended BFF Behavior

- Always send the AuthBox bearer token through unchanged.
- Always send the configured internal service key.
- Treat `code` as the primary integration signal, not only the message text.
- Use `state` from `/today` as the primary Home/Games surface state.
- Use `/summary` for one-call authenticated trivia surface composition.
- Treat `trivia_banner_url` as an app-renderable image path; if it starts with `/uploads/`, BFF should prefix the Interactive public base URL or proxy it through its media strategy.
- Use `current_attempt` from `/today` or `/summary` for resume/countdown UI when present.
- Cache or store `attempt_id` client-side between `start` and `submit`.
- Use `expires_at` from the `start` response as the source of truth for client countdowns.
- Expect `start` and `submit` to fail for unverified accounts even if the client thinks the user is verified.
- Treat `verification_required` on `/today` and `/summary` as a UI hint; `start` and `submit` remain the enforcement points.
- Use `limit` on `/leaderboards` for custom top-N leaderboard blocks beyond the default summary previews.
- Do not assume `new_badges` has content yet.

## Relevant Current Files

- `routes/api.php`
- `app/Http/Controllers/Api/Trivia/TodayTriviaController.php`
- `app/Http/Controllers/Api/Trivia/TriviaAttemptController.php`
- `app/Http/Controllers/Api/Trivia/TriviaProfileController.php`
- `app/Services/TriviaAttemptService.php`
- `app/Services/TriviaScoringService.php`
- `app/Services/TriviaQuizResolver.php`
- `app/Services/TriviaLeaderboardService.php`
- `app/Http/Middleware/VerifyJwtToken.php`
- `app/Http/Middleware/EnsureVerifiedTriviaUser.php`




