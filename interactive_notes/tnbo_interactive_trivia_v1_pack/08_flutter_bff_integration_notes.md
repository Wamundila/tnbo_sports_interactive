# 08. Flutter and BFF Integration Notes

## Client flow

```text
Flutter -> BFF -> Interactive
```

Keep the Flutter app unaware of most internal service complexity.

Important boundary:

- this Flutter client is the existing TNBO Sports app
- trivia is a feature inside that app
- do not design a separate consumer-facing interactive app flow for V1

## BFF responsibilities for trivia

- confirm the user session/token
- confirm or fetch verified account status
- attach trusted user context to Interactive request
- normalize Interactive responses for the mobile app if needed
- map internal error codes to clean app-safe errors

## Home card suggestion

Display on app home:

- title: Today's Trivia
- subtitle: 3 questions • play once • earn points
- status badge: Available / Played / Closed / Requires verification
- CTA: Play now / View results / Verify account

## Flutter screens

### 1. Trivia home widget/card

Shows availability and current streak.

### 2. Pre-start screen

Show:

- 3 questions
- 30 seconds each
- 9 points base max
- verified account required

### 3. Question screen

Show:

- progress indicator (1 of 3)
- timer bar
- question text
- optional image
- three large answer buttons

### 4. Result screen

Show:

- score total
- correct answers count
- streak after
- rank summary
- answer review

### 5. Leaderboard screen

Tabs:

- daily
- weekly
- monthly
- all-time

## App rendering rule

The app should rely on `type-safe` response fields rather than guessing.
Even in trivia, keep payloads explicit.

## Recommended client states

- loading
- available
- already_played
- closed
- verification_required
- error

## Notification opportunities

Later, BFF can coordinate notification triggers such as:

- morning quiz live
- streak risk reminder
- leaderboard update

That does not need to block V1.

## Verification UX

Because participation requires a verified account, the app should never fail silently.

If the user is not verified, show:

- why they cannot play
- a clear button to continue verification
- a return path back to trivia after verification
