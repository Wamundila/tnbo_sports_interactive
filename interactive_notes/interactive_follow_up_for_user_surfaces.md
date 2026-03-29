
# Interactive Follow-Up Note For User Surfaces

This note is for the Interactive coding agent.

It follows the user-surface thinking in:

- `interactive_notes/user_surface_recommendations.md`
- `interactive_notes/integration_notes.md`
- `interactive_notes/tnbo_interactive_trivia_v1_pack/09_system_integration_alignment.md`

## Overall Assessment

The current trivia API set is close and is good enough to begin BFF integration.

However, there are a few small follow-up changes that would make the TNBO Sports app surfaces cleaner and would reduce BFF-side guesswork.

These are product-facing API shaping suggestions, not a request to turn Interactive into a second end-user app.

## Important Product Reminder

The only end-user surface is still the TNBO Sports app.

Interactive remains:

- a backend Laravel service
- called by BFF
- responsible for gameplay/trivia logic

The requests below are purely to improve how trivia can be presented inside TNBO Sports Home and Games surfaces.

## 1. Enrich `GET /api/v1/trivia/today` With An Explicit State

Current shape already tells us:

- whether the quiz is available
- whether the user already played

That is useful, but not quite enough for UI state design.

Recommended addition:

```json
{
  "state": "available"
}
```

Suggested values:

- `available`
- `in_progress`
- `already_played`
- `not_open`
- `closed`
- `no_quiz`
- `verification_required`

Why this helps:

- BFF can map directly to Home/Games card states
- Flutter gets cleaner CTA logic
- less need to infer state from multiple fields and error paths

Important note:

- logged-out state is still handled by BFF/app, not by Interactive

## 2. Include In-Progress Attempt Summary In `/today`

For Home and Games cards, it is useful to know whether the user has an active in-progress attempt before they tap `Play` or `Continue`.

Recommended addition when relevant:

```json
{
  "state": "in_progress",
  "current_attempt": {
    "attempt_id": 12,
    "started_at": "2026-03-25T10:15:00+02:00",
    "expires_at": "2026-03-25T10:16:35+02:00"
  }
}
```

This would let BFF/Flutter show:

- `Continue`
- `Attempt expires in ...`

without having to trigger `start` first just to discover the state.

## 3. Return Quiz Metadata Even When State Is `not_open` Or `closed`

Today surfaces can still be useful even when a quiz is not playable.

Recommended behavior:

- for `not_open`, return upcoming quiz metadata if the quiz exists for today
- for `closed`, return the closed quiz metadata if useful

Example:

```json
{
  "date": "2026-03-25",
  "available": false,
  "state": "not_open",
  "quiz": {
    "id": 1,
    "title": "Today's TNBO Sports Trivia",
    "opens_at": "2026-03-25T09:00:00+02:00",
    "closes_at": "2026-03-25T21:00:00+02:00"
  }
}
```

Why this helps:

- Home can say `Opens at 09:00`
- Games can still display the trivia card instead of dropping to a null state

## 4. Add Optional `limit` To Leaderboard Endpoint

Current leaderboard output is good, but BFF/Home/Game surfaces will likely need both:

- full leaderboard screens
- small preview blocks such as top 3 or top 5

Recommended addition:

```text
GET /api/v1/trivia/leaderboards?board_type=daily&period_key=2026-03-25&limit=5
```

Rules:

- keep a server-enforced max ceiling
- ignore or clamp invalid values safely

Why this helps:

- easy `leaderboard preview` block support
- avoids fetching large lists when only a teaser is needed

## 5. No Separate Games Catalog Endpoint Is Required For V1

For V1, I do not think Interactive needs a dedicated `games catalog` endpoint yet.

Reason:

- only one real game exists: Daily Trivia
- BFF can define the Games landing composition for now
- this avoids premature abstraction

So for now:

- `Games` screen can be composed in BFF/frontend
- Interactive only needs to expose trivia capability cleanly

Later, if multiple games exist, a true `games list` endpoint can be added.

## 6. Current Endpoints Already Good For These Surfaces

These are already useful and should remain stable:

- `/today/start`
- `/attempts/{attempt}/submit`
- `/me/summary`
- `/me/history`
- `/leaderboards`

Especially good:

- `expires_at` on `start`
- stable error codes
- display-friendly leaderboard user object

## 7. Recommended Priority

If time is limited, I would prioritize only these follow-ups:

1. explicit `state` on `/today`
2. `current_attempt` on `/today` when applicable
3. `limit` support on `/leaderboards`

Everything else can wait.

## Final Recommendation

Interactive does not need a major redesign before BFF work.

It is close enough to proceed.

This note is mainly asking for a few API-shaping improvements so trivia can feel polished in:

- Home card states
- Games landing screen
- leaderboard preview blocks
- resume/in-progress UI
