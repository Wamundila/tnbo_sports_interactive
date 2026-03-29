# Predictor League V1 — Admin Dashboard Guidance

## Goal

Give admins a clean internal workflow for managing campaigns, seasons, rounds, fixtures, results, and leaderboards without developer intervention.

## Main Admin Sections

### 1. Campaigns
Admin can:
- create campaign
- set display name
- set sponsor name
- choose scope type
- choose default fixture count
- enable/disable banker
- activate/archive campaign

Suggested campaign fields:
- name
- slug
- display_name
- sponsor_name
- description
- scope_type
- default_fixture_count
- banker_enabled
- visibility
- status

### 2. Seasons
Admin can:
- create a season under campaign
- set start and end date
- define scoring config
- mark season as current
- close season

Suggested season controls:
- create
- edit
- activate
- complete
- archive

### 3. Rounds
Admin can:
- create round
- set open / close / round-close times
- attach fixtures
- publish round
- lock round manually if needed
- trigger scoring

Suggested round list columns:
- round name
- season
- opens_at
- prediction_closes_at
- round_closes_at
- status
- fixtures count
- submissions count

### 4. Fixtures
Admin can:
- import or add fixtures to a round
- reorder fixtures
- edit kickoff time
- edit team names if needed
- enter actual result
- mark postponed/cancelled

### 5. Results & Scoring
Admin can:
- see completion state per fixture
- enter missing results
- run score round
- re-run score round
- inspect score anomalies

### 6. Leaderboards
Admin can:
- preview round leaderboard
- preview monthly leaderboard
- preview season leaderboard
- refresh leaderboard
- export leaderboard CSV later

## Recommended screens

### Campaign index
Card or table of all campaigns.

### Campaign detail
Tabs:
- overview
- seasons
- competitions
- settings

### Season detail
Tabs:
- rounds
- leaderboard
- settings
- scoring rules

### Round detail
Tabs:
- fixtures
- entries
- results
- leaderboard
- scoring logs

## Important admin validations

### Campaign
- slug must be unique
- one current season per campaign at a time

### Round
- prediction close must be before round close
- round should not be activated without fixtures
- fixture count warning if below configured target

### Result entry
- prevent scoring until all non-void fixtures are finalized or explicitly allowed
- show admin warnings for postponed/cancelled fixtures

## Suggested permissions

- `predictor.view`
- `predictor.manage_campaigns`
- `predictor.manage_seasons`
- `predictor.manage_rounds`
- `predictor.manage_results`
- `predictor.run_scoring`
- `predictor.view_leaderboards`

## Useful admin actions

- duplicate previous round structure
- import fixtures from source
- bulk update kickoff times
- publish round now
- close submissions now
- finalize round
- re-score round
- refresh season aggregates

## Admin UX tips

- always show countdown and status badge
- show warnings before destructive changes
- separate draft vs active clearly
- log who changed scoring or results
- use read-only state after finalization unless explicit override

## Minimal V1 analytics on dashboard

- total participants this round
- total submissions today
- average fixtures predicted per entry
- round completion rate
- top users
- leaderboard views via analytics service later
