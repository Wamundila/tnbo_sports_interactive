
# Interactive Follow-Up Note For Summary Endpoint

This note is for the Interactive coding agent.

It follows from:

- `interactive_notes/interactive_pages_and_template.md`
- `interactive_notes/integration_notes.md`
- `interactive_notes/user_surface_recommendations.md`

## Why This Follow-Up Is Needed

The current API set is good enough for BFF integration, but building the `games_page` and reusable `daily_trivia` block from separate calls will increase round trips.

For the TNBO Sports app, we want to keep Home and Games page load times as lean as reasonably possible.

So the recommendation is:

- keep the existing trivia endpoints
- add one summary endpoint that is optimized for BFF surface composition

This is not a request for a second public app API.
It is a BFF-facing convenience endpoint for TNBO Sports surfaces.

## Recommendation

Add a single protected endpoint such as:

```text
GET /api/v1/trivia/summary
```

Alternative path names are fine, for example:

- `/api/v1/trivia/home`
- `/api/v1/trivia/surface`
- `/api/v1/trivia/games-home`

The exact name matters less than the purpose.

## Intended Use

This endpoint should let BFF populate, in one request:

- the reusable `daily_trivia` block
- the `Your Trivia Stats` strip/card
- the Games landing leaderboard preview cards

It does not need to represent the entire future `Games` page across all products.

BFF can still compose:

- trivia
- voting
- future games

from different services.

The point here is only to avoid multiple trivia-specific calls for the same screen.

## Recommended Response Shape

Suggested top-level shape:

```json
{
  "date": "2026-03-29",
  "daily_trivia": {
    "title": "Today's TNBO Sports Trivia",
    "short_description": "3 questions. Play once. Earn points.",
    "state": "available",
    "available": true,
    "requires_verified_account": true,
    "opens_at": "2026-03-29T09:00:00+02:00",
    "closes_at": "2026-03-29T21:00:00+02:00",
    "current_attempt": null,
    "today_score_total": null,
    "rank": {
      "daily": null,
      "weekly": 8,
      "monthly": 14,
      "all_time": 21
    },
    "points": {
      "today": null,
      "total": 84
    },
    "streak": {
      "current": 4,
      "best": 7
    }
  },
  "user_summary": {
    "user_id": "ts_1",
    "current_streak": 4,
    "best_streak": 7,
    "total_points": 84,
    "total_quizzes_played": 14,
    "total_quizzes_completed": 14,
    "lifetime_accuracy": 76.19,
    "today_status": {
      "played": false,
      "score_total": null
    }
  },
  "leaderboard_previews": {
    "daily": {
      "board_type": "daily",
      "period_key": "2026-03-29",
      "entries": []
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

## Important Design Goals

### 1. Support The `daily_trivia` Block Directly

The response should include enough information for BFF to build a `daily_trivia` object without calling:

- `/today`
- `/me/summary`
- `/leaderboards`

separately.

### 2. Support Games Landing Cards

The endpoint should include lightweight leaderboard previews for:

- daily
- weekly
- monthly
- all_time

Default preview limit can be:

- `5`

That matches the intended Games landing cards.

### 3. Keep Current Endpoints

Do not remove or replace:

- `/today`
- `/today/start`
- `/attempts/{attempt}/submit`
- `/me/summary`
- `/me/history`
- `/leaderboards`

Those are still useful and should remain stable.

This new endpoint is just a summary/fan-out reduction endpoint.

## Recommended State Rules

`daily_trivia.state` should follow the same meanings already documented for `/today`:

- `available`
- `in_progress`
- `already_played`
- `not_open`
- `closed`
- `no_quiz`
- `verification_required`

This keeps BFF and Flutter state mapping consistent.

## Rank Guidance

If rank is easy to retrieve in the same summary call, include:

- `daily`
- `weekly`
- `monthly`
- `all_time`

If any one rank is not available, return `null` rather than failing the whole endpoint.

That makes the endpoint resilient and still useful.

## Logged-Out Behavior

This endpoint does not need to support anonymous usage.

BFF can handle logged-out trivia card state locally.

So it is fine for this endpoint to remain:

- protected
- bearer-token based
- service-key protected

## Performance Goal

The endpoint should help BFF render authenticated trivia surfaces in one downstream request instead of several.

That is the main goal.

We are optimizing for:

- Home page trivia card
- Games landing page
- low-latency app load

## Final Recommendation

Please add one BFF-facing summary endpoint for trivia surface composition.

That should be enough for the current needs.

No larger redesign is required.
