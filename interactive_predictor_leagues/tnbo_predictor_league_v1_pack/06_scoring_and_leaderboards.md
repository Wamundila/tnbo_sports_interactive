# Predictor League V1 — Scoring and Leaderboard Rules

## Recommended V1 scoring

Use the simple scoring shape already discussed:

- correct outcome only = **3 points**
- exact score bonus = **+5 points**
- close score bonus = **+1.5 points**
- wrong outcome = **0 points**

Optional:
- banker pick = multiply that fixture's total by 2

## Scoring interpretation

### Correct outcome only
Example:
- prediction: 2-1
- actual: 3-1
- both are home win
- score = 3

### Exact score
Example:
- prediction: 2-1
- actual: 2-1
- score = 3 + 5 = 8

### Close score
Define clearly to avoid confusion.

Recommended V1 definition:
A close score applies only if:
- predicted outcome matches actual outcome
- exact score is not matched
- home score difference <= 1
- away score difference <= 1

Example:
- prediction: 2-1
- actual: 3-2
- same outcome, both scorelines off by 1
- score = 3 + 1.5 = 4.5

## Banker rule

If enabled:
- user may mark exactly one fixture as banker
- banker doubles total fixture points after calculation

Example:
- base score for banker fixture = 8
- final banker score = 16

## Recommendation for V1 simplicity

You may also choose to launch **without banker** if you want less UX and validation complexity.
If included, keep it to one banker only.

## Fixture result edge cases

### Postponed
Default recommendation:
- fixture becomes void
- award no points
- exclude from max possible points for the round

### Cancelled
Same treatment as void unless business rules say otherwise.

### Awarded result / abandoned match
Use whichever official result your editorial/admin team enters.
Scoring should use final stored official result.

## Leaderboard types

### Round leaderboard
- scope: a specific round
- ranking metric: total points earned in that round

### Monthly leaderboard
- scope: all scored rounds whose scoring finalization date falls in a calendar month
- ranking metric: total monthly points

### Season leaderboard
- scope: all scored rounds within the active season
- ranking metric: total season points

### All-time leaderboard
- scope: all scored rounds in a campaign across seasons
- ranking metric: total lifetime points

## Tie-break rules

Recommended order:
1. total points
2. exact scores count
3. correct outcomes count
4. close score count
5. earliest submission time
6. stable fallback by user id

This should be consistent across all leaderboard types.

## Personal stats

Useful stats for user profile/performance:
- rounds played
- total points
- average points per round
- correct outcomes
- exact scores
- close scores
- outcome accuracy %
- best round points
- current rank
- best season finish later

## Suggested scoring process

1. all relevant fixture results entered
2. round marked `scoring`
3. loop through round entries
4. calculate per-fixture points
5. update round entry totals
6. refresh round leaderboard
7. refresh monthly leaderboard
8. refresh season leaderboard
9. optionally refresh all-time leaderboard
10. mark round `completed`

## Formula notes

### predicted outcome
- if predicted_home_score > predicted_away_score => home_win
- if equal => draw
- if lower => away_win

### actual outcome
Same rule using actual score.

### accuracy percentage
Recommended:
```text
(correct outcomes / total eligible fixtures predicted) * 100
```

## Auditability

Store both:
- the final points totals
- the scoring breakdown per prediction

That way admins can explain why a user got a score.

## Example scored prediction response
```json
{
  "round_fixture_id": 3001,
  "predicted_score": "2-1",
  "actual_score": "2-1",
  "outcome_points": 3,
  "exact_score_points": 5,
  "close_score_points": 0,
  "banker_bonus_points": 0,
  "points_awarded": 8
}
```
