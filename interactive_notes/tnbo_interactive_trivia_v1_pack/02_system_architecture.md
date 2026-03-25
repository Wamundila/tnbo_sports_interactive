# 02. System Architecture

## High-level service structure

```text
Flutter App
   |
   v
BFF (Laravel)
   |
   v
Interactive App (Laravel)
   |
   +--> Interactive DB
   |
   +--> optional read integrations with News / Match Center datasets later
```

## App responsibility split

### AuthBoxx

Responsible for:

- login
- account identity
- account verification status
- issuing tokens used in the TNBO ecosystem

### BFF

Responsible for:

- validating app session/token at gateway level
- aggregating responses for Flutter when useful
- enforcing client-specific payload shaping
- shielding Interactive service from direct mobile-specific complexity
- passing user identity and verification context safely to Interactive

### Interactive App

Responsible for:

- admin dashboard
- trivia quiz lifecycle
- question and option storage
- attempt recording
- scoring rules
- streak calculations
- leaderboard aggregation
- user stats for the trivia feature

## Authentication and verification rule

Only a **verified TNBO Sports account** can participate.

Recommended enforcement approach:

1. BFF receives authenticated app request.
2. BFF determines current user identity and verification status.
3. BFF forwards trusted service-to-service request to Interactive.
4. Interactive still validates that the request context says:
   - user exists
   - user is verified
   - user_id is present

Do not rely on the Flutter app to declare verification state on its own.

## Recommended request context passed from BFF to Interactive

```json
{
  "user_context": {
    "user_id": "usr_123",
    "display_name": "Wamundila",
    "avatar_url": null,
    "is_verified": true
  }
}
```

This can be passed either:

- as trusted headers set by BFF, or
- in a signed internal payload, or
- via internal JWT claims for service-to-service communication

## Dashboard architecture

Interactive should have its own admin login and role system.

Recommended roles for V1:

- `interactive_super_admin`
- `interactive_admin`
- `interactive_editor`
- `interactive_analyst`

### Suggested permissions

- manage trivia dates
- manage trivia questions
- publish/unpublish quiz
- view attempts
- view leaderboard reports
- export participation data

## Future-safe internal module structure

Even though Daily Trivia is first, structure the app as if it will later host more interaction types.

Recommended module grouping:

```text
app/
  Domains/
    Trivia/
    Leaderboards/
    Users/
    Shared/
  Http/
    Controllers/
      Admin/
      Api/
```

or traditional Laravel plus strong folder grouping by feature.

## Recommended bounded contexts for V1

### Trivia

- quiz day
- questions
- options
- attempts
- answers
- result computation

### Leaderboards

- daily leaderboard
- weekly leaderboard
- monthly leaderboard
- all-time leaderboard

### User engagement

- streaks
- total points
- games played
- accuracy

## Scheduling

Use Laravel scheduler for:

- activating scheduled quiz days
- rolling leaderboard aggregates
- streak-related maintenance if needed
- analytics summary jobs

## Caching

Cache the following aggressively:

- current active quiz day metadata
- leaderboard top 50 views
- user trivia summary widget

Invalidate cache when:

- quiz is published/unpublished
- attempt is submitted
- leaderboard aggregation runs
