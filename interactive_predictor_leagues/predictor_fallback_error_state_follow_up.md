# Predictor Fallback And Error-State Follow-Up

This is a small additive note for the Interactive predictor contract.

The current predictor integration notes are already in a good state.
This follow-up only asks for one small tightening around fallback and error-state handling.

## Why This Follow-Up Exists

For the TNBO Sports app, predictor will appear in two places:

- as a reusable page-builder card/block on `games_page`
- optionally as a teaser card on `home_page`

Those surfaces need a stable way to render when the predictor summary cannot be resolved cleanly.

The current documented `predictor_surface.state` values already cover normal product states well:

- `available`
- `draft_saved`
- `submitted`
- `verification_required`
- `not_open`
- `closed`
- `completed`
- `no_round`

The only missing piece is a clear fallback state for service-side unavailability or non-business-rule failure conditions.

## Requested Small Addition

Add one more allowed `predictor_surface.state` value:

- `unavailable`

## Meaning Of `unavailable`

Use `unavailable` when the predictor summary cannot be served in a trustworthy normal business state, for example:

- campaign exists but supporting round resolution failed unexpectedly
- a dependent internal service/data read failed
- predictor is temporarily disabled internally
- a recoverable internal error occurred and the service still wants to return a safe summary payload

This state is not for normal product timing states like:

- `not_open`
- `closed`
- `completed`
- `no_round`

It is only for fallback/error-style rendering where the app should show a disabled informational card instead of guessing a normal predictor state.

## Suggested Summary Shape

Example:

```json
{
  "campaign": {
    "id": 1,
    "slug": "super_league_predictor",
    "display_name": "MTN Super League Predictor",
    "status": "active"
  },
  "predictor_surface": {
    "title": "MTN Super League Predictor",
    "short_description": "Predictor is temporarily unavailable. Please try again shortly.",
    "state": "unavailable",
    "auth_state": "unknown",
    "available": false,
    "requires_verified_account": true,
    "opens_at": null,
    "prediction_closes_at": null,
    "round_closes_at": null,
    "current_round": null,
    "entry_summary": null,
    "cta": {
      "label": "Unavailable",
      "action": "none",
      "destination": null,
      "disabled": true
    }
  },
  "user_summary": null,
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

## Why This Helps

This gives BFF and Flutter a safe, explicit fallback for:

- card rendering on Games/Home
- temporary downstream/service failures
- predictable CTA behavior

Without this, BFF may need to invent an `unavailable` state locally whenever Interactive cannot provide a normal product state.

## Scope

This is intentionally a small change.
No other route or response-shape redesign is being requested here.
