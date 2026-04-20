# TNBO Predictor League API Integration Notes

Current integration notes for the protected TNBO Sports predictor-league user APIs.

## Scope

These APIs are intended for internal TNBO integration.

Important:
- Flutter should not call this service directly.
- Expected request path is `Flutter -> BFF -> Interactive`.
- End-user identity comes from AuthBox JWTs.
- Trusted user IDs are `ts_*` IDs from JWT `sub`.
- Draft/save/submit prediction flows require a verified TNBO Sports account.
- TNBO Sports app is the only user-facing app.

## Base Path

```text
/api/v1/predictor
```

## Required Headers

All current predictor user APIs are protected and require:

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
- `sub` must match the configured TNBO Sports user pattern, default `^ts_\d+$`
- `exp` and `nbf` are enforced
- `iss` is enforced if configured
- `aud` is enforced if configured

### Verified-account enforcement
These endpoints currently require a verified account:
- `POST /rounds/{round}/draft`
- `POST /rounds/{round}/submit`

Verification is resolved from AuthBox profile lookup, not from BFF/client booleans.

The current AuthBox integration sends:
- `Authorization: Bearer <authbox_jwt>`
- `X-API-Key: <authbox_api_key>`

## Product-Facing Predictor Surface States

`GET /summary` returns a `predictor_surface.state` value so BFF and Flutter do not need to infer UI state from mixed timestamps and flags.

Current values:
- `available`
- `draft_saved`
- `submitted`
- `verification_required`
- `not_open`
- `closed`
- `completed`
- `no_round`

Notes:
- `available` means the current round is open and the user may submit picks
- `draft_saved` means the user has a draft entry for the current round
- `submitted` means the user has already submitted for the current round
- `verification_required` is a read-side hint from current AuthBox profile lookup; draft/submit remain the enforcement points

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

## Current User APIs

### 1. Get visible campaigns

```http
GET /api/v1/predictor/campaigns
```

Purpose:
- returns visible predictor campaigns for the current app surface
- includes the currently resolved season summary where available

Sample success response:

```json
{
  "items": [
    {
      "id": 1,
      "slug": "super_league_predictor",
      "display_name": "MTN Super League Predictor",
      "sponsor_name": "MTN",
      "banner_image_url": "/uploads/predictor/campaign-banners/20260420100000-example.jpg",
      "scope_type": "single_competition",
      "status": "active",
      "current_season": {
        "id": 10,
        "name": "2026 Season"
      }
    }
  ]
}
```

### 2. Get predictor summary

```http
GET /api/v1/predictor/summary?campaign_slug=super_league_predictor
```

Purpose:
- one-call predictor surface payload for BFF composition
- the preferred source for Games/Home predictor cards and lightweight predictor dashboard hero state

Sample success response:

```json
{
  "campaign": {
    "id": 1,
    "slug": "super_league_predictor",
    "display_name": "MTN Super League Predictor",
    "banner_image_url": "/uploads/predictor/campaign-banners/20260420100000-example.jpg",
    "status": "active"
  },
  "predictor_surface": {
    "title": "Predict Round 8",
    "short_description": "4 fixtures - closes in 6 hours",
    "banner_image_url": "/uploads/predictor/campaign-banners/20260420100000-example.jpg",
    "state": "available",
    "auth_state": "verified",
    "available": true,
    "requires_verified_account": true,
    "opens_at": "2026-03-29T08:00:00+02:00",
    "prediction_closes_at": "2026-03-29T18:00:00+02:00",
    "round_closes_at": "2026-03-30T12:00:00+02:00",
    "current_round": {
      "id": 44,
      "name": "Round 8"
    },
    "entry_summary": null,
    "cta": {
      "label": "Make Picks",
      "action": "open_predictor_dashboard",
      "destination": "predictor_dashboard",
      "disabled": false
    }
  },
  "user_summary": {
    "season_points": 0,
    "rounds_played": 0,
    "accuracy_percentage": 0,
    "current_rank": null
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

Notes:
- `campaign_slug` is optional; if omitted, the service resolves the first visible campaign
- use `predictor_surface.state` as the primary UI signal
- use `campaign.banner_image_url` or `predictor_surface.banner_image_url` for campaign hero/card imagery
- if `banner_image_url` starts with `/uploads/`, BFF should prefix the Interactive public base URL or proxy it through its media strategy
- `entry_summary` is only present when the user already has a round entry

### 3. Get current round for a campaign

```http
GET /api/v1/predictor/campaigns/{campaign}/current-round
```

Purpose:
- returns the currently relevant round plus fixtures and scoring rules
- useful for the predictor dashboard screen after the card/summary CTA is tapped

Sample success response:

```json
{
  "campaign": {
    "id": 1,
    "slug": "super_league_predictor",
    "display_name": "MTN Super League Predictor",
    "banner_image_url": "/uploads/predictor/campaign-banners/20260420100000-example.jpg"
  },
  "season": {
    "id": 10,
    "name": "2026 Season"
  },
  "round": {
    "id": 44,
    "name": "Round 8",
    "status": "open",
    "opens_at": "2026-03-29T08:00:00+02:00",
    "prediction_closes_at": "2026-03-29T18:00:00+02:00",
    "round_closes_at": "2026-03-30T12:00:00+02:00"
  },
  "fixtures": [
    {
      "id": 3001,
      "display_order": 1,
      "competition_name": "MTN Super League",
      "kickoff_at": "2026-03-29T13:00:00+02:00",
      "home_team": {
        "id": 101,
        "name": "Power Dynamos",
        "logo_url": null
      },
      "away_team": {
        "id": 102,
        "name": "Zesco United",
        "logo_url": null
      }
    }
  ],
  "scoring_rules": {
    "outcome_points": 3,
    "exact_score_points": 5,
    "close_score_points": 1.5,
    "banker_enabled": true,
    "banker_multiplier": 2
  }
}
```

### 4. Get current user entry for a round

```http
GET /api/v1/predictor/rounds/{round}/my-entry
```

Purpose:
- returns the authenticated user's current entry state and prediction rows for the selected round

Sample success response:

```json
{
  "entry": {
    "entry_id": 88,
    "entry_status": "submitted",
    "total_points": 11.5,
    "banker_fixture_id": 3001,
    "submitted_at": "2026-03-29T12:05:00+02:00",
    "last_edited_at": "2026-03-29T11:58:00+02:00",
    "predictions": [
      {
        "prediction_id": 1,
        "round_fixture_id": 3001,
        "predicted_home_score": 2,
        "predicted_away_score": 1,
        "predicted_outcome": "home_win",
        "is_banker": true,
        "points_awarded": 0,
        "scoring_status": "pending",
        "fixture": {
          "home_team_name": "Power Dynamos",
          "away_team_name": "Zesco United",
          "kickoff_at": "2026-03-29T13:00:00+02:00"
        }
      }
    ]
  }
}
```

Notes:
- returns `entry: null` when the user has not started this round yet

### 5. Save predictor draft

```http
POST /api/v1/predictor/rounds/{round}/draft
```

Request body:

```json
{
  "predictions": [
    {
      "round_fixture_id": 3001,
      "predicted_home_score": 2,
      "predicted_away_score": 1,
      "is_banker": true
    },
    {
      "round_fixture_id": 3002,
      "predicted_home_score": 1,
      "predicted_away_score": 1,
      "is_banker": false
    }
  ]
}
```

Sample success response:

```json
{
  "entry_id": 88,
  "entry_status": "draft",
  "saved_at": "2026-03-29T11:58:00+02:00",
  "predictions_count": 4,
  "completed_predictions_count": 4,
  "banker_fixture_id": 3001
}
```

Notes:
- requires a verified account
- user may have at most one banker selection if banker is enabled
- duplicate fixture rows are rejected

### 6. Submit predictions

```http
POST /api/v1/predictor/rounds/{round}/submit
```

Request body uses the same structure as `draft`.

Sample success response:

```json
{
  "entry_id": 88,
  "entry_status": "submitted",
  "submitted_at": "2026-03-29T12:05:00+02:00",
  "locks_at": "2026-03-29T18:00:00+02:00"
}
```

Notes:
- requires a verified account
- for rounds without partial submission enabled, all current fixtures must be supplied on submit
- once submitted, the current implementation does not allow draft edits to that same entry

### 7. Get current user performance

```http
GET /api/v1/predictor/me/performance?campaign_slug=super_league_predictor
```

Sample success response:

```json
{
  "campaign_slug": "super_league_predictor",
  "season_points": 11.5,
  "rounds_played": 1,
  "accuracy_percentage": 75,
  "exact_scores_count": 1,
  "correct_outcomes_count": 3,
  "current_rank": 12
}
```

### 8. Get current user history

```http
GET /api/v1/predictor/me/history?campaign_slug=super_league_predictor
```

Sample success response:

```json
{
  "items": [
    {
      "round_id": 44,
      "round_name": "Round 8",
      "campaign_display_name": "MTN Super League Predictor",
      "fixture_count": 4,
      "score_total": 11.5,
      "correct_outcomes_count": 3,
      "exact_scores_count": 1,
      "submitted_at": "2026-03-29T12:05:00+02:00"
    }
  ]
}
```

### 9. Get leaderboard

```http
GET /api/v1/predictor/campaigns/{campaign}/leaderboards/{boardType}?limit=5
```

Current supported `boardType` values:
- `round`
- `monthly`
- `season`

Sample success response:

```json
{
  "leaderboard_type": "season",
  "entries": [
    {
      "rank": 1,
      "user_id": "ts_1",
      "display_name": "Predictor User",
      "avatar_url": null,
      "points_total": 84.5,
      "rounds_played": 7,
      "correct_outcomes_count": 19,
      "exact_scores_count": 6,
      "accuracy_percentage": 68.2
    }
  ],
  "current_user": {
    "rank": 12,
    "points_total": 11.5
  }
}
```

## Current Error Codes To Handle

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

### Predictor state / business rules
- `PREDICTOR_VERIFICATION_REQUIRED`
- `PREDICTOR_CAMPAIGN_NOT_FOUND`
- `PREDICTOR_ROUND_NOT_OPEN`
- `PREDICTOR_ROUND_CLOSED`
- `PREDICTOR_ENTRY_NOT_FOUND`
- `PREDICTOR_INVALID_BANKER`
- `PREDICTOR_SUBMISSION_LOCKED`
- `PREDICTOR_INVALID_PAYLOAD`
- `PREDICTOR_CONFIGURATION_ERROR`
- `PREDICTOR_LEADERBOARD_NOT_FOUND`

### Generic validation / HTTP wrappers
- `VALIDATION_ERROR`
- `HTTP_ERROR`

## Current Admin Workflow

The plain Blade admin flow is now available under `/admin/predictor`.

Current admin capabilities:
- create and edit campaigns
- create and edit seasons
- create and edit rounds
- manage round fixtures
- set round status to `draft`, `open`, `locked`, or `cancelled`
- enter fixture result status and actual scores manually
- score a finalized round from the admin UI
- recalculate a completed round after result corrections
- refresh round, monthly, season, and all-time leaderboard rows during scoring

Current live-state checklist:
- campaign should be `active`
- campaign visibility should be `public`
- season should be `active`
- season should be the campaign's current season
- round should be `open`
- round should have fixtures
- `opens_at` should already be in the past
- `prediction_closes_at` should still be in the future

Current scoring checklist:
- prediction window should already be closed, or the round should already be `locked`
- every fixture should be finalized as `completed`, `postponed`, or `cancelled`
- completed fixtures must have both actual scores entered
- use `Score Round` for the first pass and `Recalculate Round` if results change later

## Current Known Gaps

These are still not fully implemented yet:
- scheduled/background scoring automation
- predictor admin reporting/analytics pages
- predictor-specific deployment/readme sections

So the current implementation now supports campaign creation, round publishing/opening, manual result entry, scoring, recalculation, and leaderboard refresh end-to-end, but not background automation or richer admin reporting yet.

## Recommended BFF Behavior

- Always send the AuthBox bearer token through unchanged.
- Always send the configured internal service key.
- Use `/summary` as the primary predictor card/block source.
- Use `predictor_surface.state` as the main Games/Home UI driver.
- Use `/current-round` for the fuller dashboard screen after CTA navigation.
- Treat `code` as the primary integration signal, not only the message text.
- Do not assume Flutter can call these routes directly.
- Do not assume submitted entries remain editable.

## Relevant Current Files

- `routes/api.php`
- `routes/web.php`
- `app/Http/Controllers/Api/Predictor/PredictorCampaignController.php`
- `app/Http/Controllers/Api/Predictor/PredictorEntryController.php`
- `app/Http/Controllers/Api/Predictor/PredictorProfileController.php`
- `app/Http/Controllers/Web/Admin/PredictorCampaignController.php`
- `app/Http/Controllers/Web/Admin/PredictorSeasonController.php`
- `app/Http/Controllers/Web/Admin/PredictorRoundController.php`
- `app/Services/PredictorCampaignResolver.php`
- `app/Services/PredictorEntryService.php`
- `app/Services/PredictorLeaderboardService.php`
- `app/Services/AdminPredictorManagementService.php`


## Predictor Summary Fallback

Additive fallback state:
- `unavailable`

Use `predictor_surface.state = unavailable` when the campaign can be resolved but normal summary resolution fails unexpectedly and Interactive still wants to return a safe summary payload instead of a broken or guessed business state.

Fallback expectations:
- `auth_state` becomes `unknown`
- `available` is `false`
- `campaign.banner_image_url` and `predictor_surface.banner_image_url` still return the configured campaign banner when available
- `current_round` is `null`
- `entry_summary` is `null`
- `user_summary` is `null`
- `leaderboard_previews.round.entries` is empty
- `leaderboard_previews.monthly.entries` is empty
- `leaderboard_previews.season.entries` is empty
- CTA is disabled with `action=none`

This fallback is for service-side degradation, not normal business timing states like `not_open`, `closed`, `completed`, or `no_round`.
## Predictor My-Entry Scored Rows

`GET /api/v1/predictor/rounds/{round}/my-entry` now returns scored-result fields on each prediction row.

Additive fields on `entry.predictions[]`:
- `actual_home_score`
- `actual_away_score`
- `points_breakdown.outcome_points`
- `points_breakdown.exact_score_points`
- `points_breakdown.close_score_points`
- `points_breakdown.banker_bonus_points`

Behavior:
- before a prediction is scored, `actual_home_score` and `actual_away_score` are `null`
- after a prediction is scored, those actual result fields are populated from the finalized fixture result
- `points_breakdown` is always present and reflects the stored scoring columns for that prediction row

Example scored row:

```json
{
  "prediction_id": 1,
  "round_fixture_id": 3001,
  "predicted_home_score": 1,
  "predicted_away_score": 0,
  "predicted_outcome": "home_win",
  "is_banker": true,
  "points_awarded": 8,
  "scoring_status": "scored",
  "actual_home_score": 1,
  "actual_away_score": 0,
  "points_breakdown": {
    "outcome_points": 3,
    "exact_score_points": 5,
    "close_score_points": 0,
    "banker_bonus_points": 0
  },
  "fixture": {
    "home_team_name": "Power Dynamos",
    "away_team_name": "Zesco United",
    "kickoff_at": "2026-03-29T13:00:00+02:00"
  }
}
```
