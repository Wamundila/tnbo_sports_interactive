# Predictor League V1 - System Integration Alignment

This addendum updates the predictor-league pack so it aligns with the system patterns already in use across TNBO Sports, BFF, AuthBox, and TNBO Interactive.

Read this before implementation.

## 1. User-Facing App Boundary

Predictor League is not a separate consumer app.

The end-user experience lives inside the existing TNBO Sports app.

Expected path:

```text
TNBO Sports Flutter app -> BFF -> TNBO Interactive
```

Implications:

- Flutter should not call Interactive directly.
- Predictor should appear under the existing `Games` area in the TNBO Sports app.
- Predictor can also appear as a reusable card/block on Home and other app-managed surfaces.
- the dedicated predictor dashboard screen in Flutter is still valid, but its data should come through BFF endpoints, not direct Interactive calls.

## 2. Auth and Verification Model

The current predictor notes should not assume BFF forwards trusted user identity or verified-state assertions.

Recommended model should match Trivia:

- BFF forwards the AuthBox bearer token unchanged
- BFF sends the internal service key
- Interactive verifies the AuthBox JWT locally
- Interactive resolves verified-account status from AuthBox-backed profile lookup or equivalent internal verification source

Do not make predictor depend on BFF-sent booleans like `is_verified` or a plain forwarded user id as the source of truth.

Use the existing TNBO user id pattern:

- `ts_<number>` from JWT `sub`

## 3. Service Protection

For production, predictor APIs should not rely only on user bearer tokens.

Keep the same protection expectations as Trivia:

- private/internal deployment where possible
- and/or `X-TNBO-Service-Key` on BFF -> Interactive requests

This should be treated as required outside local development.

## 4. BFF Route Shape Should Match Existing Patterns

The current predictor pack suggests custom app endpoints like:

- `GET /api/v1/app/home`
- `GET /api/v1/app/predictor/{campaign_slug}`

That does not match the existing BFF structure.

Current BFF patterns already in use are:

- page-builder routes like `GET /api/bff/pages/home`
- page-builder routes like `GET /api/bff/pages/games`
- protected feature routes like `GET /api/bff/interactive/trivia/...`

Recommended predictor shape:

### Page-builder surface

Use a reusable interactive block/template for predictor, for example:

- `predictor_league`

This block can appear on:

- `games_page`
- `home_page`
- later on other pages if needed

### Dedicated predictor functionality routes

Use protected BFF feature routes, for example:

- `GET /api/bff/interactive/predictor/summary?campaign_slug=...`
- `GET /api/bff/interactive/predictor/campaigns/{campaignSlug}`
- `GET /api/bff/interactive/predictor/campaigns/{campaignSlug}/current-round`
- `GET /api/bff/interactive/predictor/rounds/{roundId}/my-entry`
- `POST /api/bff/interactive/predictor/rounds/{roundId}/draft`
- `POST /api/bff/interactive/predictor/rounds/{roundId}/submit`
- `GET /api/bff/interactive/predictor/campaigns/{campaignSlug}/leaderboards/round`
- `GET /api/bff/interactive/predictor/campaigns/{campaignSlug}/leaderboards/monthly`
- `GET /api/bff/interactive/predictor/campaigns/{campaignSlug}/leaderboards/season`
- `GET /api/bff/interactive/predictor/me/performance?campaign_slug=...`
- `GET /api/bff/interactive/predictor/me/history?campaign_slug=...`

The exact route naming can vary, but it should stay consistent with the current BFF `interactive/*` convention.

## 5. Add A One-Call Predictor Summary Endpoint In Interactive

This is the biggest recommendation.

Do not force BFF to compose the predictor home card or Games card from many round trips.

For predictor, add a protected Interactive summary endpoint from the start, similar to Trivia `/api/v1/trivia/summary`.

Recommended Interactive endpoint:

- `GET /api/v1/predictor/summary?campaign_slug=...`

Purpose:

- provide one-call data for the predictor card/block and Games landing surface
- reduce round trips for Flutter and BFF
- keep predictor state shaping inside the gameplay service

Recommended payload shape:

```json
{
  "campaign": {
    "id": 1,
    "slug": "super_league_predictor",
    "display_name": "MTN Super League Predictor",
    "status": "active"
  },
  "predictor_surface": {
    "title": "Predict Round 8",
    "short_description": "4 fixtures - closes in 3h 12m",
    "state": "available",
    "auth_state": "verified",
    "available": true,
    "requires_verified_account": true,
    "opens_at": "2026-04-03T08:00:00Z",
    "prediction_closes_at": "2026-04-05T11:00:00Z",
    "round_closes_at": "2026-04-06T18:00:00Z",
    "current_round": {
      "id": 44,
      "name": "Round 8"
    },
    "entry_summary": {
      "entry_id": 880,
      "entry_status": "draft",
      "saved_at": "2026-04-05T09:10:00Z",
      "submitted_at": null,
      "predictions_count": 4,
      "completed_predictions_count": 4,
      "banker_fixture_id": 3001
    },
    "cta": {
      "label": "Continue Picks",
      "action": "open_predictor_dashboard",
      "destination": "predictor_dashboard",
      "disabled": false
    }
  },
  "user_summary": {
    "season_points": 84.5,
    "rounds_played": 7,
    "accuracy_percentage": 68.2,
    "current_rank": 12
  },
  "leaderboard_previews": {
    "round": {
      "entries": []
    },
    "monthly": {
      "entries": []
    },
    "season": {
      "entries": []
    }
  }
}
```

This is the predictor equivalent of Trivia's summary endpoint and is the right source for:

- a `predictor_league` page-builder block
- the Games landing card
- lightweight dashboard hero content

## 6. State Shaping Should Be Product-Facing

Predictor should expose a clear product-facing state field for the surface card.

Suggested `predictor_surface.state` values:

- `signed_out`
- `verification_required`
- `available`
- `draft_saved`
- `submitted`
- `not_open`
- `closed`
- `completed`
- `no_round`
- `unavailable`

Why:

- BFF and Flutter should not infer all UI states from mixed timestamps and booleans
- this follows the same successful pattern already used for Trivia

## 7. Predictor Block / Template Recommendation

Add a new interactive block/template in BFF:

- `predictor_league`

Recommended V1 presentation type:

- `card`

Recommended runtime object shape from BFF:

- `item` for the predictor surface summary
- `meta.user_summary`
- `meta.leaderboard_previews`

This should parallel the current `daily_trivia` block structure where practical.

## 8. Logged-Out And Unverified Handling

The predictor card may appear even when the user is:

- logged out
- logged in but not verified

Recommended handling:

### Logged out
- BFF/page-builder can render a signed-out predictor card locally or via fallback state
- CTA: `Sign In to Play`

### Logged in but not verified
- predictor summary should return `verification_required`
- CTA: `Verify Account`
- read-only browsing is still acceptable if desired

### Verified and round open
- `available` or `draft_saved`
- CTA: `Make Picks` or `Continue Picks`

### Submitted already
- `submitted`
- CTA: `View Entry`

## 9. Interactive Should Own Gameplay Truth

BFF should not reconstruct predictor gameplay state by joining many low-level endpoints if it can be avoided.

Interactive should own:

- round availability state
- entry state
- prediction editability
- banker rules
- scoring status
- leaderboard rank snapshots

This keeps BFF thinner and Flutter simpler.

## 10. Match Center Relationship

Predictor will likely depend on football fixture data.

Recommended boundary:

- Interactive may import or sync fixture/result data from Match Center or another trusted source
- Interactive should still expose predictor-ready payloads through predictor APIs
- BFF should not have to join Match Center fixtures with predictor entries on the fly for core predictor screens

Use source references where helpful, but keep predictor APIs presentation-ready.

## 11. Error Envelope And Codes

Follow the same machine-readable error-code style already used in Trivia.

Suggested examples:

- `PREDICTOR_VERIFICATION_REQUIRED`
- `PREDICTOR_CAMPAIGN_NOT_FOUND`
- `PREDICTOR_ROUND_NOT_OPEN`
- `PREDICTOR_ROUND_CLOSED`
- `PREDICTOR_ENTRY_NOT_FOUND`
- `PREDICTOR_INVALID_BANKER`
- `PREDICTOR_SUBMISSION_LOCKED`
- `PREDICTOR_CONFIGURATION_ERROR`

BFF and Flutter should build behavior from codes, not only messages.

## 12. Leaderboards Should Support Preview Limits

For Games landing and predictor cards, BFF will need small leaderboard snippets.

Predictor leaderboard endpoints should support a limit parameter from the start.

For example:

- `GET /api/v1/predictor/campaigns/{campaign_slug}/leaderboards/round?limit=5`
- `GET /api/v1/predictor/campaigns/{campaign_slug}/leaderboards/monthly?limit=5`
- `GET /api/v1/predictor/campaigns/{campaign_slug}/leaderboards/season?limit=5`

Or expose those previews directly inside `/summary`.

## 13. Recommended First App Surface

For V1 inside TNBO Sports app:

- add a Predictor card on `games_page`
- optionally add a Predictor teaser card on `home_page`
- tapping the card opens the dedicated Flutter predictor dashboard screen
- the dashboard itself is not page-builder-managed, but its data should still come through BFF predictor endpoints

## Summary Of Required Adjustments

Before implementation, update the pack assumptions so they reflect:

- TNBO Sports app is the only user-facing app
- BFF remains the app-facing gateway
- Interactive verifies JWTs locally and resolves verification itself
- predictor should have a one-call summary endpoint like Trivia
- predictor should expose product-facing surface states
- BFF should use a reusable `predictor_league` block/template on Games/Home
- predictor-specific dashboard data should still flow through BFF protected endpoints
