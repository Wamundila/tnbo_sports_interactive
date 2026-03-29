# Predictor League V1 — Product Scope

## Goal

Create a **fast, habit-forming prediction game** where users predict outcomes and scores for a small set of fixtures per round, earn points, and compete on leaderboards.

## Product Principles

1. **Quick to play**
   - target completion time: 2–3 minutes
   - default round size: 4 fixtures

2. **Easy to understand**
   - users predict scoreline for each selected fixture
   - points are awarded using clear rules

3. **Competitive**
   - round leaderboard
   - monthly leaderboard
   - season leaderboard
   - private leagues later or in controlled V1.1

4. **Flexible commercially**
   - campaign name can be sponsor-driven
   - campaigns can be competition-specific
   - future support for multiple campaigns in the same engine

## Recommended V1 Product Decision

### Build one engine, start with one campaign

For V1:
- support one active public campaign first
- scope that campaign to **one competition**
- keep schema and services ready for multiple campaigns later

This gives product clarity while keeping the backend extensible.

## Core Concepts

### Predictor Campaign
Top-level playable product.

Examples:
- Super League Predictor
- ABSA Cup Predictor
- TNBO Tournament Predictor

### Season
A time-bounded playable period inside a campaign.

Fields:
- start date
- end date
- status
- default scoring config

### Round
A weekly or event-based prediction window inside a season.

Fields:
- open time
- prediction close time
- round close time
- status

### Round Fixture
A selected fixture included in a round for prediction.

Fields:
- home team
- away team
- competition
- kickoff time
- actual score
- display order

## V1 Functional Scope

### User-facing
- browse active campaign
- see current season and current round
- view countdown to close
- submit predictions for all fixtures in a round
- optionally edit predictions before close time
- view results after fixtures complete
- view round, monthly, and season leaderboard
- view personal performance summary

### Admin-facing
- create/edit campaign
- create/edit season
- create/edit round
- attach fixtures to round
- open/close round
- enter or sync results
- trigger scoring/recalculation
- view campaign stats

## Out of Scope for strict V1
These should be optional or deferred:
- multi-competition mixed rounds in UI
- advanced private leagues
- paid entry / betting style functionality
- prizes and payout mechanics
- anti-cheat anomaly tooling beyond basic protections
- social comments/chat
- push notification orchestration inside Interactive itself

## Participation Rule

Only users with a **verified TNBO Sports account** may submit predictions.

Users who are not verified may:
- browse campaign info
- see fixtures and public leaderboards
- be blocked from prediction submission until verified

## Suggested V1 Screens

1. Predictor home
2. Current round / submit picks
3. Round results
4. Leaderboards
5. My performance

## Success Metrics

- users opening predictor per round
- prediction submission rate
- completion rate per round
- repeat participation next round
- average rounds played per user per month
- leaderboard views per active user
- edit-before-deadline rate
