# Single Choice Poll Results Visibility Findings

Date: 2026-03-30

## Purpose

This note records the final Interactive findings after reviewing:
- `interactive_single_choice_poll/single_choice_poll_results_visibility_backend_note.md`
- `interactive_notes/single_choice_poll_environment_verification_followup.md`

## Poll Checked

Poll slug:
- `absa-cup-fpott`

Stored local poll fields:
- `status = closed`
- `visibility = public`
- `result_visibility_mode = final_results`
- `open_at = 2026-03-30T01:47:00Z`
- `close_at = 2026-03-30T11:47:00Z`

## What Was Initially Confirmed

Guest/local route checks showed:
- `GET /api/v1/polls/absa-cup-fpott` returned visible final results
- `GET /api/v1/polls/absa-cup-fpott/results` returned `200` with visible final results

That made an environment mismatch look likely.

## What The Logs Revealed

After checking the local Laravel log more closely, authenticated requests for user `ts_4` were repeatedly showing:
- `GET /api/v1/polls/absa-cup-fpott` -> `200`
- `GET /api/v1/polls/absa-cup-fpott/results` -> `403`
- error: `POLL_RESULTS_HIDDEN`

So the issue was real in Interactive, but only for an authenticated user who had already voted.

## Root Cause

The bug was in `app/Services/PollService.php`.

Previous behavior in `surfaceState()`:
- it checked `if ($vote instanceof PollVote)` before checking whether the poll window was already closed
- so a closed poll with an existing user vote was classified as `already_voted`
- that state then fell into hidden-results logic for some routes

Effect:
- guest users could see final results on a closed `final_results` poll
- authenticated users who had already voted could incorrectly get `POLL_RESULTS_HIDDEN`

## Fix Applied

`surfaceState()` was corrected so closed/scheduled state is resolved before `already_voted`.

That means for a closed poll:
- the surface now becomes `results_only` or `closed` based on policy
- an authenticated user who already voted still gets visible final results when policy allows them

## Regression Coverage Added

A regression test was added for the exact failing case:
- closed poll
- `result_visibility_mode = final_results`
- authenticated user already has a vote
- both detail and results endpoints must remain results-visible

Relevant test:
- `tests/Feature/Api/PollGameplayTest.php`

## Verification Status

After the fix:
- `php artisan test --filter=PollGameplayTest` passes
- full `php artisan test` passes

Current full suite status:
- `37 tests`
- `311 assertions`

## Updated Conclusion

The problem was not only an environment-verification question.
A real Interactive backend bug existed for this authenticated already-voted closed-poll case, and it has now been fixed.

If Flutter/BFF was seeing:
- detail route behaving like `already_voted` with hidden results
- results route returning `POLL_RESULTS_HIDDEN`

that behavior is now explained by the old Interactive logic and should no longer occur once the fixed code is what BFF is reaching.

## Remaining Deployment Check

One environment check still matters:
- BFF/Flutter must be pointed at the Interactive instance running this fixed code

There is evidence of multiple local ports in play on this machine:
- `.env` has `APP_URL=http://127.0.0.1:8004`
- Flutter/BFF reported `http://192.168.1.165:8001`
- both `:8001` and `:8004` were listening locally during verification

So after the code fix, the remaining risk is deployment/runtime targeting rather than contract logic.

## Reference Files

Implementation fix:
- `app/Services/PollService.php`

Regression test:
- `tests/Feature/Api/PollGameplayTest.php`

Related contract note:
- `poll_integration_notes.md`
