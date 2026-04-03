# Single Choice Poll Result Payload Consistency Follow-Up

This is a small follow-up request after reviewing `poll_integration_notes.md`.

The current poll contract is broadly ready for BFF integration.

The only remaining request is about keeping option/result payloads consistent enough for Flutter to render the post-vote state cleanly without extra merging logic.

## Why This Follow-Up Is Needed

Current summary/detail payloads return rich option rows like:

- `id`
- `title`
- `subtitle`
- `description`
- `image_url`
- `video_url`
- `thumbnail_url`
- `badge_text`
- `stats_summary`
- `display_order`
- `entity_type`
- `entity_id`
- `is_selected`
- `vote_count`
- `percentage`

But the current vote-success example returns:

- `options: []`
- `results.options[]` with a thinner shape using `option_id`, `title`, `vote_count`, `percentage`

That is enough for a minimal result list, but it is weaker for a richer poll detail screen where Flutter may want to keep rendering the same nominee cards after the user votes.

## Requested Improvement

Please make the post-vote/result contract more consistent with the summary/detail contract.

Recommended approaches:

### Preferred
Return a populated `options` array in vote-success responses, with the same option object shape used by summary/detail, but now enriched with:

- `is_selected`
- `vote_count`
- `percentage`

This would let Flutter transition directly from pre-vote to post-vote using one stable option-card model.

### Acceptable Alternative
If `results.options[]` remains the main post-vote data structure, make it rich enough to support card rendering by including at least:

- `id`
- `title`
- `subtitle`
- `image_url`
- `thumbnail_url`
- `badge_text`
- `stats_summary`
- `is_selected`
- `vote_count`
- `percentage`

Also prefer `id` over `option_id` so option identifiers stay consistent across endpoints.

## Recommended Goal

Try to keep one canonical option object shape across:

- summary
- detail
- vote success
- results

That reduces BFF normalization work and makes Flutter less error-prone.

## Not A Blocker

This is not a blocker for initial BFF work.

The current contract is usable.

This request is mainly to improve:

- post-vote rendering quality
- contract consistency
- frontend implementation simplicity