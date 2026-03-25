# 01. Product and Scope

## Product purpose

Daily Trivia should become a lightweight daily habit inside TNBO Sports:

- open app
- play a short quiz
- earn points
- protect streak
- check leaderboard

This makes it a good complement to News and Match Center because it adds an active participation layer rather than only content consumption.

## Why this fits TNBO

The uploaded notes already point toward a very good V1 shape:

- 3 questions per day
- 30 seconds per question
- 3 options per question
- 3 points per correct answer
- streak bonuses
- daily, weekly, monthly, and all-time leaderboards
- admin-generated question supply

## V1 user story

A verified TNBO Sports user opens the Flutter app, sees a "Today's Trivia" card, starts the quiz, answers 3 questions, receives a final score, sees their streak and rank impact, and checks the leaderboard.

## V1 boundaries

### Include

- verified-user-only participation
- one daily trivia set per date
- 3 multiple-choice questions per quiz
- one attempt per user per day
- score calculation
- streak tracking
- global leaderboards
- dashboard for admin content management
- dashboard for viewing participation and performance
- API access through BFF

### Exclude for V1

- cash rewards
- voucher redemption
- private leagues unless already easy to support
- live multiplayer trivia rooms
- open-ended text answers
- audio/video question types
- direct app-to-interactive communication bypassing BFF
- advanced anti-cheat systems beyond core server-side validation

## Recommended launch shape

### User experience

1. Home card: "Today's Trivia"
2. Pre-start screen: rules, countdown note, points possible
3. Quiz question flow
4. Result screen
5. Leaderboard screen
6. Profile stats block inside trivia section

### Admin experience

1. Create trivia days
2. Add/edit 3 questions for a specific date
3. Publish or schedule quiz
4. View attempts and scores
5. View leaderboard summaries
6. Monitor question quality and participation

## Important product rule

The daily quiz should feel **short, fair, and consistent**.

Do not change the structure too often in V1. Consistency matters more than novelty at the beginning.
