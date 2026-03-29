# Predictor League V1 — API Contract

## Integration pattern

Flutter app -> BFF -> Interactive API

BFF should remain the public-facing integration layer. Interactive should expose service APIs that BFF consumes.

## Auth rule

Prediction submission endpoints require:
- authenticated user
- verified TNBO Sports account

Read-only endpoints may be public or authenticated depending on your app policy.

## Suggested endpoint groups

### Public/read endpoints
- `GET /api/v1/predictor/campaigns`
- `GET /api/v1/predictor/campaigns/{campaign_slug}`
- `GET /api/v1/predictor/campaigns/{campaign_slug}/current-round`
- `GET /api/v1/predictor/rounds/{round_id}`
- `GET /api/v1/predictor/rounds/{round_id}/leaderboard`
- `GET /api/v1/predictor/campaigns/{campaign_slug}/leaderboards/monthly`
- `GET /api/v1/predictor/campaigns/{campaign_slug}/leaderboards/season`

### Authenticated user endpoints
- `GET /api/v1/predictor/me/eligibility`
- `GET /api/v1/predictor/rounds/{round_id}/my-entry`
- `POST /api/v1/predictor/rounds/{round_id}/draft`
- `POST /api/v1/predictor/rounds/{round_id}/submit`
- `PUT /api/v1/predictor/rounds/{round_id}/predictions/{prediction_id}`
- `GET /api/v1/predictor/me/performance`
- `GET /api/v1/predictor/me/history`

### Admin endpoints
- `POST /api/v1/admin/predictor/campaigns`
- `POST /api/v1/admin/predictor/seasons`
- `POST /api/v1/admin/predictor/rounds`
- `POST /api/v1/admin/predictor/rounds/{round_id}/fixtures`
- `POST /api/v1/admin/predictor/rounds/{round_id}/score`
- `POST /api/v1/admin/predictor/rounds/{round_id}/recalculate`

## Endpoint details

### GET /api/v1/predictor/campaigns
Returns visible campaigns.

Example response:
```json
{
  "data": [
    {
      "id": 1,
      "slug": "super_league_predictor",
      "display_name": "MTN Super League Predictor",
      "sponsor_name": null,
      "scope_type": "single_competition",
      "status": "active",
      "current_season": {
        "id": 11,
        "name": "2026 Season"
      }
    }
  ]
}
```

### GET /api/v1/predictor/campaigns/{campaign_slug}/current-round
Example response:
```json
{
  "data": {
    "campaign": {
      "id": 1,
      "slug": "super_league_predictor",
      "display_name": "MTN Super League Predictor"
    },
    "season": {
      "id": 11,
      "name": "2026 Season"
    },
    "round": {
      "id": 44,
      "name": "Round 8",
      "status": "open",
      "opens_at": "2026-04-03T08:00:00Z",
      "prediction_closes_at": "2026-04-05T11:00:00Z",
      "round_closes_at": "2026-04-06T18:00:00Z"
    },
    "fixtures": [
      {
        "id": 3001,
        "display_order": 1,
        "competition_name": "MTN Super League",
        "kickoff_at": "2026-04-05T12:00:00Z",
        "home_team": {
          "id": 10,
          "name": "Power Dynamos",
          "logo_url": null
        },
        "away_team": {
          "id": 11,
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
}
```

### GET /api/v1/predictor/me/eligibility
Example response:
```json
{
  "data": {
    "authenticated": true,
    "verified_account_required": true,
    "is_verified": true,
    "can_submit_predictions": true,
    "reason": null
  }
}
```

### POST /api/v1/predictor/rounds/{round_id}/draft
Purpose:
- create or update draft predictions before final submit

Example request:
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
      "predicted_home_score": 0,
      "predicted_away_score": 0,
      "is_banker": false
    }
  ]
}
```

Example response:
```json
{
  "data": {
    "entry_id": 880,
    "entry_status": "draft",
    "saved_at": "2026-04-05T09:10:00Z"
  }
}
```

### POST /api/v1/predictor/rounds/{round_id}/submit
Purpose:
- final submission for the round

Example request:
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
    },
    {
      "round_fixture_id": 3003,
      "predicted_home_score": 0,
      "predicted_away_score": 2,
      "is_banker": false
    },
    {
      "round_fixture_id": 3004,
      "predicted_home_score": 3,
      "predicted_away_score": 2,
      "is_banker": false
    }
  ]
}
```

Example response:
```json
{
  "data": {
    "entry_id": 880,
    "entry_status": "submitted",
    "submitted_at": "2026-04-05T10:22:00Z",
    "locks_at": "2026-04-05T11:00:00Z"
  }
}
```

### GET /api/v1/predictor/rounds/{round_id}/my-entry
Example response:
```json
{
  "data": {
    "entry_id": 880,
    "entry_status": "submitted",
    "total_points": 11.5,
    "banker_fixture_id": 3001,
    "predictions": [
      {
        "prediction_id": 1,
        "round_fixture_id": 3001,
        "predicted_home_score": 2,
        "predicted_away_score": 1,
        "predicted_outcome": "home_win",
        "points_awarded": 8,
        "scoring_status": "scored"
      }
    ]
  }
}
```

### GET /api/v1/predictor/rounds/{round_id}/leaderboard
Example response:
```json
{
  "data": {
    "leaderboard_type": "round",
    "round": {
      "id": 44,
      "name": "Round 8"
    },
    "entries": [
      {
        "rank": 1,
        "user_id": "ts_1",
        "display_name": "Wamundila Songwe",
        "avatar_url": null,
        "points_total": 17.5,
        "exact_scores_count": 2,
        "correct_outcomes_count": 4
      }
    ]
  }
}
```

### GET /api/v1/predictor/me/performance
Example response:
```json
{
  "data": {
    "campaign_slug": "super_league_predictor",
    "season_points": 84.5,
    "rounds_played": 7,
    "accuracy_percentage": 68.2,
    "exact_scores_count": 6,
    "correct_outcomes_count": 19,
    "current_rank": 12
  }
}
```

## Validation rules

### Prediction submission
- user must be verified
- round must be open
- fixture must belong to round
- one banker max if banker enabled
- all required fixtures present unless partial submissions are enabled
- scores must be non-negative integers

## Error response examples

### Unverified account
```json
{
  "message": "Verified TNBO Sports account required.",
  "code": "PREDICTOR_VERIFICATION_REQUIRED"
}
```

### Round closed
```json
{
  "message": "Prediction window is closed for this round.",
  "code": "PREDICTOR_ROUND_CLOSED"
}
```

### Invalid banker
```json
{
  "message": "Only one banker selection is allowed.",
  "code": "PREDICTOR_INVALID_BANKER"
}
```

## Event tracking suggestions

Interactive or BFF should emit analytics events like:
- `predictor_round_viewed`
- `predictor_draft_saved`
- `predictor_submitted`
- `predictor_round_results_viewed`
- `predictor_leaderboard_viewed`
- `predictor_performance_viewed`
