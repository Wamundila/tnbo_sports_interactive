# Single Choice Poll V1 - System Integration Alignment

This addendum updates the single-choice-poll pack so it aligns with the system patterns already in use across TNBO Sports, BFF, AuthBox, and TNBO Interactive.

Read this before implementation.

## 1. User-Facing App Boundary

Single Choice Poll is not a separate consumer app.

The end-user experience lives inside the existing TNBO Sports app.

Expected path:

```text
TNBO Sports Flutter app -> BFF -> TNBO Interactive
```

Implications:

- Flutter should not call Interactive directly.
- Polls can appear as reusable cards/blocks on `games_page`, `home_page`, and later on article or match pages.
- a dedicated full poll screen in Flutter is still valid, but its data should come through BFF routes, not direct Interactive calls.

## 2. Auth and Verification Model

The poll module should follow the same trust model already used for Trivia and Predictor.

Recommended model:

- BFF forwards the AuthBox bearer token unchanged when present
- BFF sends the internal service key on BFF -> Interactive requests
- Interactive verifies the AuthBox JWT locally
- Interactive resolves the TNBO user id from JWT `sub`
- Interactive determines verified-account eligibility from trusted AuthBox-backed identity state, not from client- or BFF-sent booleans

Do not make poll eligibility depend on BFF-sent values like:

- `user_id`
- `is_verified`
- `email_verified`

Use the existing TNBO user id pattern:

- `ts_<number>`

## 3. Service Protection

For production, poll APIs should not rely only on end-user bearer tokens.

Keep the same protection expectations as Trivia and Predictor:

- private/internal deployment where possible
- and/or `X-TNBO-Service-Key` on BFF -> Interactive requests

This should be treated as required outside local development.

## 4. BFF Route Shape Should Match Existing Patterns

Do not design poll as a standalone mobile-facing API surface.

Current BFF patterns already in use are:

- page-builder routes like `GET /api/bff/pages/home`
- page-builder routes like `GET /api/bff/pages/games`
- protected feature routes like `GET /api/bff/interactive/trivia/...`
- protected feature routes like `GET /api/bff/interactive/predictor/...`

Recommended poll shape:

### Page-builder surface

Use a reusable interactive block/template in BFF, for example:

- `single_choice_poll`

This block can appear on:

- `games_page`
- `home_page`
- later on article or match pages if a poll is tied to that content

### Dedicated poll functionality routes

Use BFF feature routes, for example:

- `GET /api/bff/interactive/polls/summary?poll_slug=...`
- `GET /api/bff/interactive/polls/{pollSlug}`
- `POST /api/bff/interactive/polls/{pollSlug}/vote`
- `GET /api/bff/interactive/polls/{pollSlug}/results`

The exact route naming can vary, but it should stay consistent with the current BFF `interactive/*` convention.

## 5. Add A One-Call Poll Summary Endpoint In Interactive

This is the biggest recommendation.

Do not force BFF to build the poll card from several round trips.

For poll, add a canonical Interactive summary endpoint from the start.

Recommended Interactive endpoint:

- `GET /api/v1/polls/summary?poll_slug=...`

Recommended lookup behavior:

- support `poll_slug` as the primary stable identifier for page-builder blocks and Flutter deep links
- keep id-based lookup support for admin/internal use if needed
- optionally allow a future fallback mode such as `featured=1` or `context_type/context_id`, but slug support should be the default contract for BFF surfaces

Purpose:

- provide one-call data for the poll card/block and Games landing surface
- reduce round trips for Flutter and BFF
- keep vote availability and results visibility shaping inside Interactive

Recommended payload shape:

```json
{
  "poll": {
    "id": "poll_001",
    "slug": "player-of-the-month-march-2026",
    "type": "single_choice",
    "category": "player_of_the_month",
    "title": "TNBO Player of the Month",
    "question": "Who should be Player of the Month?",
    "description": "Vote for the player whose performances stood out this month.",
    "status": "live",
    "open_at": "2026-04-01T08:00:00Z",
    "close_at": "2026-04-07T20:00:00Z",
    "login_required": true,
    "verified_account_required": true,
    "result_visibility_mode": "live_percentages"
  },
  "poll_surface": {
    "title": "Player of the Month",
    "short_description": "Vote now and see live percentages.",
    "state": "available",
    "auth_state": "verified",
    "available": true,
    "can_vote": true,
    "has_voted": false,
    "my_vote_option_id": null,
    "requires_login": true,
    "requires_verified_account": true,
    "results_visible": false,
    "results_state": "hidden",
    "cta": {
      "label": "Vote Now",
      "action": "open_poll",
      "destination": "poll_detail",
      "disabled": false
    }
  },
  "options": [
    {
      "id": "opt_001",
      "title": "Player A",
      "subtitle": "Club X",
      "description": "4 goals and 2 assists in 5 matches.",
      "image_url": "https://cdn.example.com/player-a.jpg",
      "video_url": null,
      "thumbnail_url": null,
      "badge_text": "Top scorer",
      "stats_summary": "4G 2A",
      "display_order": 1,
      "entity_type": "player",
      "entity_id": 101
    }
  ],
  "results": {
    "total_votes": 1200,
    "winner_option_id": "opt_001",
    "options": []
  }
}
```

This endpoint should be enough for:

- a `single_choice_poll` page-builder block
- a Games landing poll card
- a lightweight full poll detail screen

## 6. Read Access Should Support Guest Poll Surfaces

This is an important difference from Trivia and Predictor.

Poll cards may appear on Home, Games, article, or match surfaces even when the user is not signed in.

Recommended approach:

- poll summary and poll detail read endpoints should be readable without auth when the poll itself is meant to be discoverable
- when a bearer token is present, Interactive can enrich the response with user-specific fields like `has_voted`, `my_vote_option_id`, and eligibility state
- vote submission should still require auth when `login_required = true`

This keeps poll cards usable for:

- signed-out teaser states
- read-only result views
- editorial discovery surfaces

If a specific poll must be fully private, that can remain configurable, but the default product assumption should support public read plus controlled vote eligibility.

## 7. State Shaping Should Be Product-Facing

Poll should expose a clear product-facing state field for the card/surface.

Suggested `poll_surface.state` values:

- `signed_out`
- `verification_required`
- `available`
- `already_voted`
- `scheduled`
- `closed`
- `results_only`
- `unavailable`

Why:

- BFF and Flutter should not infer all UI states from mixed timestamps and booleans
- this follows the same successful pattern already used for Trivia and Predictor

Recommended supporting fields:

- `results_visible`
- `results_state` with values like `hidden`, `live`, `final`

That lets Flutter know whether to render:

- selectable options
- disabled options with selected marker
- live percentages
- final winner state
- hidden-results placeholder

## 8. Vote Submission Should Return The Updated Surface

Do not make the app vote and then immediately perform a second read just to know what to render.

Recommended vote success response behavior:

- return the updated `poll_surface`
- return `results` if the configured result mode allows them
- return `my_vote_option_id`
- return `submitted_at`

This is better than a minimal success payload because the app can transition to the correct post-vote UI immediately.

## 9. Poll Block / Template Recommendation

Add a new interactive block/template in BFF:

- `single_choice_poll`

Recommended V1 presentation type:

- `card`

Recommended runtime shape from BFF:

- `item` for the poll surface summary
- `items` for the option list when needed on the card surface
- `meta.poll`
- `meta.results`

Recommended admin config for the block:

- `front_end_title`
- `poll_slug`
- optional card chrome colors for the surrounding TNBO app surface

The option media and nominee styling should come from the poll payload itself, not from block config.

## 10. Logged-Out And Unverified Handling

The poll card may appear even when the user is:

- logged out
- logged in but not verified

Recommended handling:

### Logged out
- summary can still load
- `state = signed_out`
- options can be visible in read-only mode
- CTA: `Sign In to Vote`

### Logged in but not verified
- `state = verification_required`
- voting disabled
- CTA: `Verify Account`

### Eligible and poll live
- `state = available`
- voting enabled
- CTA: `Vote Now`

### Already voted
- `state = already_voted`
- selected option clearly marked
- show results according to result visibility rules
- CTA can become `View Results` or remain disabled

### Poll scheduled
- `state = scheduled`
- show opening time

### Poll closed
- `state = closed` or `results_only`
- show final results according to policy

## 11. Error Envelope And Codes

Follow the same machine-readable error-code style already used in Trivia and Predictor.

Suggested examples:

- `POLL_VERIFICATION_REQUIRED`
- `POLL_LOGIN_REQUIRED`
- `POLL_NOT_FOUND`
- `POLL_NOT_OPEN`
- `POLL_CLOSED`
- `POLL_ALREADY_VOTED`
- `POLL_OPTION_INVALID`
- `POLL_RESULTS_HIDDEN`
- `POLL_CONFIGURATION_ERROR`

BFF and Flutter should build behavior from codes, not only messages.

## 12. Results Visibility Should Stay In Interactive

Do not make BFF reconstruct result-policy behavior.

Interactive should own:

- whether results are visible before vote
- whether results are visible after vote
- whether only final results are visible
- when winner data is allowed to show

BFF should forward or lightly normalize those decisions, not reimplement them.

## 13. Recommended First App Surfaces

For V1 inside TNBO Sports app:

- add a poll card on `games_page`
- optionally add a poll teaser card on `home_page`
- later allow poll placements on article or match pages when `context_type/context_id` is used
- tapping the card can open a dedicated Flutter poll screen when richer option media needs more space

## Summary Of Required Adjustments

Before implementation, update the pack assumptions so they reflect:

- TNBO Sports app is the only user-facing app
- BFF remains the app-facing gateway
- Interactive verifies JWTs locally and resolves verification itself
- poll should have a one-call summary endpoint for card/block surfaces
- read endpoints should support guest-readable poll surfaces where appropriate
- poll should expose product-facing surface states
- vote submission should return the updated post-vote surface
- BFF should use a reusable `single_choice_poll` block/template on Games, Home, and later contextual pages