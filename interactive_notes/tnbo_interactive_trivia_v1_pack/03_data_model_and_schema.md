# 03. Data Model and Schema

## Design principles

- keep trivia data normalized enough for correctness
- keep leaderboard reads fast
- keep room for future game types

## Core tables

### 1. admins

If not shared with another internal admin auth source, Interactive can maintain its own admin users.

Suggested columns:

- id
- name
- email
- password
- status
- last_login_at
- created_at
- updated_at

### 2. roles / permissions

Use Spatie if preferred.

### 3. trivia_quizzes

Represents the daily quiz for a specific date.

Suggested columns:

- id
- quiz_date (unique)
- title
- short_description
- status (`draft`, `scheduled`, `published`, `closed`, `archived`)
- opens_at
- closes_at
- question_count_expected default 3
- time_per_question_seconds default 30
- points_per_correct default 3
- streak_bonus_enabled boolean
- created_by_admin_id
- published_by_admin_id nullable
- published_at nullable
- metadata json nullable
- created_at
- updated_at

### 4. trivia_questions

Suggested columns:

- id
- trivia_quiz_id
- position
- question_text
- image_url nullable
- explanation_text nullable
- source_type nullable (`manual`, `news`, `match_center`, `historic`)
- source_ref nullable
- difficulty nullable (`easy`, `medium`, `hard`)
- status (`draft`, `active`, `retired`)
- created_at
- updated_at

Constraints:

- unique(trivia_quiz_id, position)

### 5. trivia_question_options

Suggested columns:

- id
- trivia_question_id
- position
- option_text
- is_correct boolean
- created_at
- updated_at

Constraints:

- unique(trivia_question_id, position)
- exactly one correct option per question should be enforced at app validation level and ideally with tests

### 6. trivia_attempts

One attempt per user per quiz date.

Suggested columns:

- id
- trivia_quiz_id
- user_id
- started_at
- submitted_at nullable
- status (`in_progress`, `submitted`, `expired`, `invalidated`)
- score_base default 0
- score_bonus default 0
- score_total default 0
- correct_answers_count default 0
- wrong_answers_count default 0
- unanswered_count default 0
- time_taken_seconds nullable
- streak_before default 0
- streak_after default 0
- ranking_snapshot nullable
- client_type nullable (`flutter_android`, `flutter_ios`, `web`)
- created_at
- updated_at

Constraints:

- unique(trivia_quiz_id, user_id)

### 7. trivia_attempt_answers

Suggested columns:

- id
- trivia_attempt_id
- trivia_question_id
- trivia_question_option_id nullable
- is_correct boolean default false
- answered_at nullable
- response_time_ms nullable
- created_at
- updated_at

Constraints:

- unique(trivia_attempt_id, trivia_question_id)

### 8. user_trivia_profiles

Keeps quick summary stats for each user.

Suggested columns:

- id
- user_id unique
- current_streak default 0
- best_streak default 0
- total_points default 0
- total_correct_answers default 0
- total_wrong_answers default 0
- total_quizzes_played default 0
- total_quizzes_completed default 0
- lifetime_accuracy decimal(5,2) default 0
- last_played_quiz_date nullable
- created_at
- updated_at

### 9. leaderboard_entries

Materialized leaderboard rows for fast reads.

Suggested columns:

- id
- board_type (`daily`, `weekly`, `monthly`, `all_time`)
- period_key
- user_id
- points
- quizzes_played
- correct_answers
- accuracy decimal(5,2)
- avg_score decimal(6,2) nullable
- rank_position
- created_at
- updated_at

Constraints:

- unique(board_type, period_key, user_id)
- index(board_type, period_key, rank_position)

### 10. trivia_activity_logs

Optional but useful for admin auditing.

Suggested columns:

- id
- actor_type (`admin`, `system`, `user`)
- actor_id nullable
- event_name
- reference_type
- reference_id
- metadata json nullable
- created_at

## Suggested period keys

- daily: `2026-03-25`
- weekly: `2026-W13`
- monthly: `2026-03`
- all_time: `all`

## Important indexing

Add indexes on:

- trivia_quizzes.quiz_date
- trivia_quizzes.status
- trivia_attempts.user_id
- trivia_attempts.trivia_quiz_id
- trivia_attempts.submitted_at
- trivia_attempt_answers.trivia_question_id
- leaderboard_entries.board_type + period_key + rank_position

## Notes on user identity

Use the AuthBoxx/TNBO user id as the foreign reference even if the Interactive DB does not own the master user record.

Do not duplicate the full user table unless there is a very deliberate reason.
Store only lightweight denormalized display fields if needed for leaderboard rendering caches.
