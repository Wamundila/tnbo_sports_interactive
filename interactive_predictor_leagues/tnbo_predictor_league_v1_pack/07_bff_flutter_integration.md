# Predictor League V1 — BFF and Flutter Integration Notes

## Recommended request path

Flutter should not call Interactive directly.

Use:
Flutter app -> BFF -> Interactive

## Why

- keeps service boundaries consistent
- lets BFF aggregate predictor with news, match center, and other app modules
- central place for auth/session handling
- easier to reshape responses for app UI
- better control over rollout and caching

## BFF responsibilities

### 1. Auth and identity propagation
BFF should:
- validate app session/token
- know the TNBO user id
- know whether account is verified
- forward identity context to Interactive

### 2. Response shaping
BFF can:
- combine predictor home card with other app home modules
- normalize dates and countdown payloads
- flatten leaderboard responses if needed

### 3. Feature gating
BFF may:
- hide predictor from unlaunched environments
- show read-only state for unverified users
- gate by region, app version, or campaign availability later

## Suggested BFF-facing endpoints

### Home card
BFF endpoint:
- `GET /api/v1/app/home`

BFF may embed:
```json
{
  "interactive_cards": [
    {
      "type": "predictor_current_round",
      "campaign_slug": "super_league_predictor",
      "title": "Predict Round 8",
      "subtitle": "4 matches • closes in 3h 12m",
      "cta_label": "Make Picks"
    }
  ]
}
```

### Predictor detail
BFF endpoint:
- `GET /api/v1/app/predictor/{campaign_slug}`

BFF fetches from Interactive:
- campaign
- current round
- eligibility
- user's existing picks
- top leaderboard snippet

## Flutter screen suggestions

### 1. Predictor home
Show:
- campaign hero
- current round card
- countdown to deadline
- leaderboard preview
- my stats snippet

### 2. Round pick screen
Show:
- 4 fixtures in order
- team logos and kickoff time
- numeric pickers or compact steppers
- optional banker selector
- save draft button
- submit button
- preview summary

### 3. Results screen
Show:
- predicted score vs actual score
- points per fixture
- round total
- rank movement if available

### 4. Leaderboards screen
Tabs:
- round
- monthly
- season

### 5. My performance
Show:
- total points
- rank
- accuracy
- rounds played
- exact scores

## UX rules

- allow draft autosave
- clearly show deadline
- lock fields once round closes
- distinguish saved draft vs final submitted
- show read-only explanation if not verified

## Verified account handling

If user is not verified:
- predictor may still be browsable
- submit controls disabled
- show CTA to verify account

Suggested app copy:
- "Verify your TNBO Sports account to join Predictor League."

## Suggested analytics events

From app or BFF:
- predictor_home_opened
- predictor_round_opened
- predictor_draft_saved
- predictor_submit_clicked
- predictor_submitted
- predictor_results_opened
- predictor_leaderboard_opened
- predictor_verify_cta_clicked

## Suggested notification opportunities later

- round opened
- 3 hours to close
- 30 minutes to close
- results are in
- you climbed the leaderboard
