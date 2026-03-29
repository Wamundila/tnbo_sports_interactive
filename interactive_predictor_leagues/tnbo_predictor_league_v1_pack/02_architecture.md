# Predictor League V1 — Architecture

## Recommended Architecture

Build Predictor League as a **domain module inside the Interactive Laravel app**.

### High-level flow

Flutter app -> BFF -> Interactive Laravel app

Related supporting systems:
- **AuthBox** for identity and token verification
- **BFF** for frontend-facing aggregation, access control, and response shaping
- optional external match/fixture source for importing fixtures and results

## Why this structure

- Interactive owns gameplay rules and state
- BFF stays as the single app-facing entry point
- Flutter does not need direct service-to-service complexity
- future games can share patterns inside Interactive

## Internal domain boundaries

### 1. Campaign Management
Responsible for:
- campaign creation
- season setup
- campaign branding/settings
- campaign status

### 2. Round Management
Responsible for:
- round lifecycle
- round fixture selection
- open/close timing
- round state transitions

### 3. Prediction Management
Responsible for:
- drafts
- submissions
- edits before deadline
- banker selection if enabled

### 4. Result & Scoring
Responsible for:
- actual scores
- scoring calculations
- recalculation
- score audit records

### 5. Leaderboards & Stats
Responsible for:
- round leaderboard
- monthly leaderboard
- season leaderboard
- user performance summaries

### 6. Access Control
Responsible for:
- verified account requirement
- user eligibility checks
- admin role checks

## Suggested Laravel structure

```text
app/
  Domains/
    Predictor/
      Actions/
      DTOs/
      Enums/
      Events/
      Exceptions/
      Http/
        Controllers/Admin/
        Controllers/Api/
        Requests/
        Resources/
      Jobs/
      Models/
      Policies/
      Services/
      Support/
```

## Suggested state model

### Campaign status
- draft
- scheduled
- active
- completed
- archived

### Season status
- draft
- active
- completed
- archived

### Round status
- draft
- open
- locked
- scoring
- completed
- cancelled

## Timing model

A round should have three important timestamps:
- `opens_at`
- `prediction_closes_at`
- `round_closes_at`

### Meaning
- before `opens_at`: visible or hidden depending on config, no submission
- between `opens_at` and `prediction_closes_at`: picks can be created or edited
- between `prediction_closes_at` and `round_closes_at`: no edits, fixtures in progress or awaiting completion
- after `round_closes_at`: round can be finalized and leaderboards updated

## Fixture result ingestion

Support two modes:

### A. Manual admin entry
Best for V1 reliability.

### B. Imported result feed
Best for later.
If imported, still support manual correction and re-score.

## Scoring execution pattern

Use queued jobs for scoring so recalculation is safe and repeatable.

Suggested jobs:
- `ScoreRoundJob`
- `RecalculateRoundJob`
- `RefreshRoundLeaderboardJob`
- `RefreshSeasonLeaderboardJob`
- `RefreshMonthlyLeaderboardJob`

## Important design decision

Never store only the leaderboard totals.
Always store:
- prediction records
- per-fixture scoring results
- round totals
- aggregated leaderboard entries

This makes re-scoring possible when rules or results change.

## Access pattern

### App/API access
- user token validated upstream through BFF
- BFF forwards user identity and verified-state context to Interactive
- Interactive still validates authorization for gameplay actions

### Admin access
- internal dashboard auth
- admin roles and permissions in Interactive
- optionally federated with AuthBox later

## Reliability notes

- lock round edits at close time
- prevent duplicate final submissions for same user and round
- keep score audit trail
- allow safe rescoring
- use idempotent scoring jobs

## Suggested integrations later
- notifications service
- analytics/events service
- match center for fixture/result source
- social/private league invite sharing
