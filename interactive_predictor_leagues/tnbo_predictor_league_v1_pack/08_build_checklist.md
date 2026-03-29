# Predictor League V1 — Build Checklist

## Phase 1 — Foundation
- [ ] create Predictor domain/module in Interactive Laravel app
- [ ] define enums and statuses
- [ ] create migrations for campaigns, seasons, rounds, fixtures, entries, predictions, leaderboards
- [ ] create seeders/factories for local development
- [ ] define policies and permissions for admin

## Phase 2 — Admin core
- [ ] campaign CRUD
- [ ] season CRUD
- [ ] round CRUD
- [ ] round fixture management
- [ ] result entry screen
- [ ] score round action
- [ ] recalculate round action

## Phase 3 — Public/user API
- [ ] list visible campaigns
- [ ] get campaign detail
- [ ] get current round
- [ ] get my eligibility
- [ ] save draft
- [ ] submit predictions
- [ ] get my entry
- [ ] get round leaderboard
- [ ] get monthly leaderboard
- [ ] get season leaderboard
- [ ] get my performance

## Phase 4 — Validation and scoring
- [ ] prevent unverified users from submitting
- [ ] prevent submissions after prediction close
- [ ] enforce one entry per user per round
- [ ] enforce one banker only if enabled
- [ ] implement scoring service
- [ ] implement leaderboard refresh service
- [ ] add audit trail / logs

## Phase 5 — BFF integration
- [ ] create BFF service client for Interactive predictor endpoints
- [ ] add predictor home card to app home payload
- [ ] add campaign detail endpoint
- [ ] pass verified-state to frontend
- [ ] standardize error codes for UI handling

## Phase 6 — Flutter/UI integration
- [ ] predictor home card
- [ ] current round screen
- [ ] draft save state
- [ ] final submit confirmation
- [ ] results breakdown
- [ ] leaderboard tabs
- [ ] my performance screen
- [ ] verify account blocked state

## Phase 7 — Background jobs
- [ ] auto-lock rounds at close time
- [ ] round scoring job
- [ ] leaderboard refresh job
- [ ] monthly aggregation refresh
- [ ] cleanup/reporting jobs as needed

## Phase 8 — QA scenarios
- [ ] verified user submits successful round
- [ ] unverified user blocked
- [ ] draft edited before deadline
- [ ] submission blocked after deadline
- [ ] postponed fixture handled correctly
- [ ] manual result correction triggers recalculation
- [ ] rank tie-break behaves correctly
- [ ] app shows read-only state after round close

## Phase 9 — Nice V1.1 additions
- [ ] private leagues
- [ ] invite links
- [ ] share card for results
- [ ] banker analytics
- [ ] campaign-specific sponsor branding
- [ ] multi-competition curated rounds
- [ ] import fixtures from match center automatically

## Suggested implementation order
1. schema
2. admin CRUD
3. current round read endpoints
4. save draft and submit flow
5. manual results and scoring
6. leaderboards
7. BFF integration
8. Flutter rollout
9. analytics
