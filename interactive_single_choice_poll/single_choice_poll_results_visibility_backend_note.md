# Single Choice Poll Results Visibility Follow-up For Interactive

This note is for the Interactive agent.

## Why This Is Being Sent To Interactive

The current evidence points to an Interactive visibility/state issue, not a BFF shaping issue.

BFF now normalizes the poll payload shape for Flutter, but it does **not** decide whether results are visible.

Current BFF behavior:

- `GET /api/bff/interactive/polls/{pollSlug}` forwards Interactive detail, then normalizes it to `item`, `items`, and `meta`
- `GET /api/bff/interactive/polls/{pollSlug}/results` forwards Interactive results, then normalizes it to `item`, `items`, and `meta`
- if Interactive returns `403 POLL_RESULTS_HIDDEN`, BFF forwards that response

So if the app is seeing `POLL_RESULTS_HIDDEN`, that decision is coming from Interactive.

## Current Runtime Problem

On device, Flutter is seeing this behavior for a poll it expected to have visible final results:

1. `GET /api/bff/interactive/polls/{pollSlug}` returns a poll detail payload without usable result stats
2. Flutter then calls `GET /api/bff/interactive/polls/{pollSlug}/results`
3. The results route responds with:
   - `POLL_RESULTS_HIDDEN`
   - message similar to `Poll results are not visible yet`

That means the current live runtime is not matching the previously saved sample payloads that showed a closed/results-visible poll.

## What Needs To Be Checked In Interactive

Please inspect the **live Interactive data and rules** for the exact poll slug Flutter is testing and confirm:

1. whether the poll is actually closed in Interactive
2. whether the poll's final results are actually published/available
3. what `result_visibility_mode` is on the live poll record
4. whether `/api/v1/polls/{pollSlug}` and `/api/v1/polls/{pollSlug}/results` are applying different visibility logic
5. whether guest vs authenticated requests are affecting result visibility unexpectedly
6. whether the previously shared sample payload is stale and no longer representative of live behavior

## Routes To Compare In Interactive

Please compare the live Interactive responses for the same poll slug:

- `GET /api/v1/polls/{pollSlug}`
- `GET /api/v1/polls/{pollSlug}/results`

If useful, compare both:

- guest read
- authenticated read

## Expected Behavior

If the poll is closed and final results are intended to be visible, then at least one of these should be true:

- detail route returns visible result stats
- results route returns visible result stats

Visible result stats means Flutter should ultimately be able to receive:

- `winner_option_id`
- `total_votes`
- option `vote_count`
- option `percentage`

## Requested Outcome

Please confirm one of these outcomes explicitly:

1. the live poll really should have visible final results, and Interactive will correct the visibility behavior
2. the live poll should still hide results, and the old sample payloads need to be updated because they are no longer representative

If a correction is needed, the most important thing is that the live Interactive behavior for:

- `/api/v1/polls/{pollSlug}`
- `/api/v1/polls/{pollSlug}/results`

matches the intended result-visibility policy for the poll's actual live state.