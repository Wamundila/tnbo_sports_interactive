# Single Choice Poll Environment Verification Follow-up For Interactive

This note is for the Interactive agent.

## Current Assessment

After the latest Flutter debug output, the issue no longer looks like a slug mismatch.

Flutter is now reporting:

- `routeSlug = absa-cup-fpott`
- `surfaceSlug = absa-cup-fpott`
- `slugMatch = true`

So the most likely remaining cause is an environment or data-source mismatch between:

- the Interactive instance that was verified locally
- the Interactive instance currently being reached by BFF / Flutter at runtime

## Why This Follow-up Exists

Interactive already confirmed that, locally, the following poll behaves correctly:

- `absa-cup-fpott`
- `status = closed`
- `result_visibility_mode = final_results`
- detail route returns visible final results
- results route returns visible final results

But Flutter runtime is still seeing:

- detail route behaves like `already_voted` with hidden results
- results route returns `Poll results are not visible yet`

That strongly suggests BFF is not hitting the same effective Interactive environment/data state that was checked locally.

## What Needs To Be Compared Next

Please compare the exact BFF-facing runtime environment with the local Interactive environment you already checked.

### Confirm the Interactive base URL BFF is expected to use

The BFF runtime shown by Flutter is using:

- `baseUrl = http://192.168.1.165:8001`

Please confirm whether that is in fact the same Interactive instance you verified locally.

## Routes To Compare On The BFF-Facing Interactive Instance

Please check these routes on the exact instance BFF is calling:

- `GET /api/v1/polls/absa-cup-fpott`
- `GET /api/v1/polls/absa-cup-fpott/results`

## What To Confirm

For that exact Interactive instance, please confirm:

1. the poll `absa-cup-fpott` exists
2. its stored fields are still:
   - `status = closed`
   - `visibility = public`
   - `result_visibility_mode = final_results`
3. the same poll record has the same `open_at` and `close_at` values you previously checked
4. the runtime code on that instance is the same code version that produced the correct local result
5. no stale cache, old deployment, or different database connection is causing older visibility behavior

## Expected Outcome

If BFF is hitting the same correct Interactive instance, then both of these should be true for `absa-cup-fpott`:

- `GET /api/v1/polls/absa-cup-fpott` returns a results-visible surface
- `GET /api/v1/polls/absa-cup-fpott/results` returns `200` with final results

If that is not true on the instance behind BFF, then the issue is not in Flutter or BFF contract shaping.
It is in the specific Interactive runtime/environment that BFF is currently reaching.