# Predictor League V1 — Data Model and Schema Guidance

## Design Principles

- one shared engine
- many campaigns supported
- one user entry per round per campaign
- store both predictions and scored outcomes
- make rescoring possible without data loss

## Core Tables

### predictor_campaigns
Top-level game product.

Suggested fields:
- id
- uuid
- name
- slug
- display_name
- sponsor_name nullable
- description nullable
- scope_type enum (`single_competition`, `multi_competition`, `curated`)
- default_fixture_count
- banker_enabled boolean
- status
- visibility
- starts_at nullable
- ends_at nullable
- metadata json nullable
- created_by
- updated_by
- timestamps

### predictor_campaign_competitions
Maps competitions allowed in a campaign.

Suggested fields:
- id
- campaign_id
- competition_id
- competition_name_snapshot nullable
- sort_order
- timestamps

This table allows a campaign to stay single-competition now but expand later.

### predictor_seasons
Suggested fields:
- id
- campaign_id
- name
- slug
- start_date
- end_date
- status
- scoring_config json
- rules_text nullable
- is_current boolean
- timestamps

### predictor_rounds
Suggested fields:
- id
- season_id
- name
- round_number nullable
- opens_at
- prediction_closes_at
- round_closes_at
- status
- fixture_count
- allow_partial_submission boolean default false
- leaderboard_frozen_at nullable
- notes nullable
- timestamps

### predictor_round_fixtures
Suggested fields:
- id
- round_id
- source_fixture_id nullable
- competition_id nullable
- competition_name_snapshot
- home_team_id nullable
- away_team_id nullable
- home_team_name_snapshot
- away_team_name_snapshot
- home_team_logo_url nullable
- away_team_logo_url nullable
- kickoff_at
- display_order
- result_status enum (`pending`, `live`, `completed`, `postponed`, `cancelled`)
- actual_home_score nullable
- actual_away_score nullable
- result_entered_at nullable
- result_source nullable
- metadata json nullable
- timestamps

## User participation tables

### predictor_round_entries
Represents a user's participation in a round.

Suggested fields:
- id
- round_id
- campaign_id
- season_id
- user_id
- anon_id nullable
- entry_status enum (`draft`, `submitted`, `locked`, `scored`, `void`)
- submitted_at nullable
- last_edited_at nullable
- total_points decimal(8,2) default 0
- correct_outcomes_count default 0
- exact_scores_count default 0
- close_score_count default 0
- banker_fixture_id nullable
- banker_multiplier decimal(5,2) nullable
- metadata json nullable
- timestamps

Unique index:
- unique(round_id, user_id)

### predictor_predictions
One row per entry per fixture.

Suggested fields:
- id
- round_entry_id
- round_fixture_id
- predicted_home_score
- predicted_away_score
- predicted_outcome enum (`home_win`, `draw`, `away_win`)
- is_banker boolean default false
- was_submitted boolean default false
- points_awarded decimal(8,2) default 0
- outcome_points decimal(8,2) default 0
- exact_score_points decimal(8,2) default 0
- close_score_points decimal(8,2) default 0
- banker_bonus_points decimal(8,2) default 0
- scoring_status enum (`pending`, `scored`, `void`)
- scoring_notes nullable
- scored_at nullable
- timestamps

Unique index:
- unique(round_entry_id, round_fixture_id)

## Leaderboards

### predictor_leaderboard_entries
Materialized leaderboard table for fast reads.

Suggested fields:
- id
- leaderboard_type enum (`round`, `monthly`, `season`, `all_time`, `private_league`)
- campaign_id
- season_id nullable
- round_id nullable
- private_league_id nullable
- leaderboard_period_key nullable
- user_id
- rank nullable
- points_total decimal(10,2)
- rounds_played default 0
- correct_outcomes_count default 0
- exact_scores_count default 0
- close_score_count default 0
- accuracy_percentage decimal(5,2) nullable
- metadata json nullable
- refreshed_at nullable
- timestamps

Indexes:
- (leaderboard_type, campaign_id)
- (leaderboard_type, season_id)
- (leaderboard_type, round_id)
- (leaderboard_type, leaderboard_period_key)
- (leaderboard_type, private_league_id)

## Optional V1.1 social tables

### predictor_private_leagues
- id
- campaign_id
- owner_user_id
- name
- invite_code
- status
- max_members
- timestamps

### predictor_private_league_members
- id
- private_league_id
- user_id
- role
- joined_at
- status
- timestamps

## Score rule storage

Store campaign/season scoring rules in JSON for flexibility.

Example:
```json
{
  "outcome_points": 3,
  "exact_score_points": 5,
  "close_score_points": 1.5,
  "banker_enabled": true,
  "banker_multiplier": 2
}
```

## Recommended source of truth

Authoritative scoring inputs:
1. actual result on `predictor_round_fixtures`
2. scoring config on season or campaign
3. user predictions on `predictor_predictions`

## Example migration order

1. predictor_campaigns
2. predictor_campaign_competitions
3. predictor_seasons
4. predictor_rounds
5. predictor_round_fixtures
6. predictor_round_entries
7. predictor_predictions
8. predictor_leaderboard_entries
9. predictor_private_leagues
10. predictor_private_league_members

## Suggested enums

### scope_type
- single_competition
- multi_competition
- curated

### round status
- draft
- open
- locked
- scoring
- completed
- cancelled

### entry status
- draft
- submitted
- locked
- scored
- void
