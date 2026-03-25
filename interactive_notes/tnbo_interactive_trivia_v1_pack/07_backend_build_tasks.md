# 07. Backend Build Tasks

## Phase 1 - Foundation

- create new Laravel app for Interactive if not already created
- set up environment, database, queues, scheduler, cache
- configure admin authentication
- set up roles and permissions
- set up service-to-service auth with BFF

## Phase 2 - Trivia data model

- create migrations for trivia tables
- create Eloquent models
- define relationships
- add factories/seeders for test data
- add request validation classes

## Phase 3 - Admin dashboard

- quizzes list page
- create/edit quiz page
- question builder UI
- publish/close actions
- attempts table
- leaderboard view
- exports if time allows

## Phase 4 - User APIs

- get today's quiz summary
- start quiz
- submit attempt
- get my summary
- get leaderboard
- get my history

## Phase 5 - Game services

Create dedicated services such as:

- `TriviaQuizResolver`
- `TriviaAttemptService`
- `TriviaScoringService`
- `TriviaStreakService`
- `TriviaLeaderboardService`

## Phase 6 - Scheduler / jobs

- publish scheduled quizzes
- close expired quizzes
- refresh leaderboard entries
- recalculate leaderboard snapshots if needed

## Phase 7 - Quality and testing

### Must-have tests

- verified user can start quiz
- unverified user cannot start quiz
- user cannot play twice in same day
- only one correct option allowed per question
- score is correctly computed
- streak increments correctly
- daily leaderboard rank ordering is deterministic
- admin cannot publish invalid quiz

## Suggested Laravel structure

```text
app/
  Actions/
  Services/
  Models/
  Http/
    Controllers/
    Requests/
  Policies/
  Jobs/
  Support/
```

## Suggested implementation order

1. migrations + models
2. admin CRUD for quizzes/questions
3. start/submit gameplay APIs
4. scoring and streak services
5. leaderboards
6. dashboard reporting polish

## Delivery definition for V1

V1 is complete when:

- admin can create and publish a daily quiz
- verified user can play once through BFF
- result is stored and scored correctly
- user summary is visible
- leaderboard is visible
- tests cover core integrity rules
