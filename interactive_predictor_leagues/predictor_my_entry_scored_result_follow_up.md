# Predictor My-Entry Scored Result Follow-Up

This note is for the TNBO Interactive agent.

## Why This Follow-Up Exists

Flutter is successfully rendering predictor entry rows from:

- `GET /api/v1/predictor/rounds/{round}/my-entry`

It can already show fixture-level points from `points_awarded`.

However, after a round is scored, Flutter also wants to show the actual match result on each prediction row, for example:

- predicted score: `1 - 0`
- actual result: `1 - 0`
- points awarded: `+6`

At the moment, the documented `my-entry.predictions[]` shape does not explicitly expose actual scored result fields.

## Current Documented Shape

Current documented row shape is roughly:

```json
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
```

That is enough for pre-score and pending-score rendering, but not for a complete scored-result row.

## Requested Addition

When a fixture has been scored, enrich `my-entry.predictions[]` with actual result score fields.

Recommended additive fields:

```json
{
  "prediction_id": 1,
  "round_fixture_id": 3001,
  "predicted_home_score": 1,
  "predicted_away_score": 0,
  "predicted_outcome": "home_win",
  "is_banker": true,
  "points_awarded": 6,
  "scoring_status": "scored",
  "actual_home_score": 1,
  "actual_away_score": 0,
  "fixture": {
    "home_team_name": "Power Dynamos",
    "away_team_name": "Zesco United",
    "kickoff_at": "2026-03-29T13:00:00+02:00"
  }
}
```

## Optional But Recommended Addition

If available, also expose a points breakdown so Flutter can show more detailed score explanations later.

Example:

```json
{
  "points_awarded": 6,
  "points_breakdown": {
    "outcome_points": 1,
    "exact_score_points": 0,
    "goal_diff_points": 1,
    "banker_points": 4
  }
}
```

This is optional for now.
The actual result scores are the main requirement.

## Nullability / Fallback Behavior

Recommended behavior:

- before a fixture is scored:
  - `actual_home_score` = `null`
  - `actual_away_score` = `null`
  - `scoring_status` stays `pending`
- after a fixture is scored:
  - `actual_home_score` and `actual_away_score` are populated
  - `points_awarded` is populated
  - `scoring_status` becomes `scored`

That gives Flutter a stable rule for when to render:

- `Result Score Pending`
- versus the actual score line

## Why This Should Be Added In Interactive

BFF currently proxies `my-entry` through unchanged.

This means:
- BFF is not the right place to reconstruct actual result scores from separate predictor internals
- Flutter should not need to infer actual scores from points alone
- the gameplay service is the correct owner of scored fixture truth

## Summary

Please either:

1. confirm that `my-entry.predictions[]` already returns actual result score fields in live payloads and update the integration note accordingly,

or

2. add these fields to the contract:

- `actual_home_score`
- `actual_away_score`
- optionally `points_breakdown`

This will let Flutter render fully scored prediction rows cleanly on the predictor dashboard.
