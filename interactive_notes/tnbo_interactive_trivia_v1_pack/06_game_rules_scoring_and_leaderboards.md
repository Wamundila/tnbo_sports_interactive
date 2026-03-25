# 06. Game Rules, Scoring, and Leaderboards

## Base rules

- one quiz per calendar date
- one attempt per verified user per quiz date
- 3 questions per quiz
- 3 options per question
- 30 seconds allowed per question at UI level
- 3 base points per correct answer
- max base score = 9

## Time rule note

For V1, the countdown can be enforced mainly on the client for user experience.

The backend should still record response times and optionally reject obviously invalid submissions later if needed, but avoid overengineering strict timer policing in the first version.

## Recommended streak logic

A streak means the user completed Daily Trivia on consecutive quiz dates.

### Suggested streak updates

- if user completed yesterday's quiz and also completes today's quiz -> streak +1
- if user missed the previous quiz date -> streak resets to 1
- if today's submission is invalidated -> do not count it

### Suggested bonus model

Keep bonus simple:

- 3-day streak completion bonus: +3
- 7-day streak completion bonus: +6
- 14-day streak completion bonus: +9

You can later refine, but V1 should remain simple and explainable.

## Score formula

```text
score_total = score_base + score_bonus
score_base = correct_answers_count * points_per_correct
```

## Leaderboard options

### Daily leaderboard

Purpose:

- fast daily competition
- tied to current quiz date

Ranking priority:

1. points desc
2. correct_answers desc
3. time_taken_seconds asc
4. submitted_at asc
5. user_id asc as final deterministic tiebreaker

### Weekly leaderboard

Aggregate by ISO week.

Inputs:

- total points in week
- quizzes played
- correct answers
- accuracy

### Monthly leaderboard

Aggregate by calendar month.

### All-time leaderboard

Aggregate across all completed quizzes.

## Accuracy formula

```text
accuracy = total_correct_answers / (total_correct_answers + total_wrong_answers) * 100
```

Ignore unanswered if you want a simpler interpretation, or include them in denominator if the product team prefers stricter accuracy.

## Recommended badging for later

Not required for V1, but compatible later:

- Perfect Score
- 3-Day Run
- 7-Day Run
- Weekly Winner
- Monthly Top 10

## Anti-abuse basics for V1

Implement only practical checks:

- one attempt per user per day
- verified account required
- server-side answer ownership validation
- immutable result after submit
- optional IP/device logging in metadata

## Result review policy

After submission, it is good to show:

- selected answer
- correct answer
- short explanation

This improves learning and makes trivia feel higher quality.
