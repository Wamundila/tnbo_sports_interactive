# Single Choice Poll Integration Notes

## Scope

This note documents the current Interactive user API surface for the Single Choice Poll module.

Source of truth for system behavior remains:
- `interactive_single_choice_poll/tnbo_single_choice_poll_v1_pack/09_system_integration_alignment.md`

Key integration rules:
- TNBO Sports app is the only user-facing app
- Flutter should call BFF, not Interactive directly
- BFF should call Interactive with `X-TNBO-Service-Key`
- guest poll reads are supported for discoverable/public polls
- authenticated votes use AuthBox JWT verification inside Interactive
- trusted user ids come from JWT `sub` and must be `ts_*`
- verified-account gating comes from AuthBox-backed profile data, not BFF booleans

Media note:
- admin-uploaded poll cover/banner/option media is stored on Laravel's `public` storage disk under `storage/app/public/uploads/polls/...`
- API payloads expose app-relative URLs like `/storage/uploads/polls/covers/...`
- BFF should prefix the Interactive public base URL or proxy media through its own media strategy

## Current Interactive Endpoints

### Guest-readable poll summary

`GET /api/v1/polls/summary?poll_slug={slug}`

Headers:
- `Accept: application/json`
- `X-TNBO-Service-Key: <internal-service-key>`
- optional `Authorization: Bearer <authbox-jwt>`

### Poll detail

`GET /api/v1/polls/{pollSlug}`

Headers:
- same as summary

Behavior:
- currently returns the same payload shape as summary

### Poll results

`GET /api/v1/polls/{pollSlug}/results`

Headers:
- same as summary

Behavior:
- returns results only when the poll's result visibility policy allows it
- otherwise returns `403` with `POLL_RESULTS_HIDDEN`
- now also returns a top-level `options` array using the same canonical option shape as summary/detail

### Submit vote

`POST /api/v1/polls/{pollSlug}/vote`

Headers:
- `Accept: application/json`
- `X-TNBO-Service-Key: <internal-service-key>`
- `Authorization: Bearer <authbox-jwt>`

Body:
```json
{
  "option_id": 12,
  "client": "flutter",
  "session_id": "device-session-123"
}
```

Behavior:
- verifies the JWT locally
- resolves user id from JWT `sub`
- checks one-vote-per-user for the poll
- validates the option belongs to the poll
- enforces verified-account requirement when enabled on the poll
- returns the updated poll surface and results if policy allows them

## Canonical Option Object

Interactive now uses one canonical option row shape across:
- summary
- detail
- vote success
- results

Shape:
```json
{
  "id": 11,
  "title": "Player A",
  "subtitle": "Club A",
  "description": "4 goals in 3 matches.",
  "image_url": null,
  "video_url": null,
  "thumbnail_url": null,
  "badge_text": null,
  "stats_summary": null,
  "display_order": 1,
  "entity_type": null,
  "entity_id": null,
  "is_selected": false,
  "vote_count": null,
  "percentage": null
}
```

When results are visible:
- `vote_count` is populated
- `percentage` is populated
- `is_selected` reflects the current user's vote when known

## Summary / Detail Response Shape

```json
{
  "poll": {
    "id": 4,
    "slug": "player-of-the-month",
    "type": "single_choice",
    "category": "player_of_the_month",
    "title": "Player of the Month",
    "question": "Who should win Player of the Month?",
    "description": "Vote for the standout player.",
    "status": "live",
    "open_at": "2026-03-30T10:00:00+02:00",
    "close_at": "2026-04-02T20:00:00+02:00",
    "login_required": true,
    "verified_account_required": true,
    "result_visibility_mode": "live_percentages",
    "sponsor_name": "TNBO",
    "cover_image_url": null,
    "banner_image_url": null,
    "context_type": null,
    "context_id": null
  },
  "poll_surface": {
    "title": "Player of the Month",
    "short_description": "Vote for the standout player.",
    "state": "available",
    "auth_state": "verified",
    "available": true,
    "can_vote": true,
    "has_voted": false,
    "my_vote_option_id": null,
    "requires_login": true,
    "requires_verified_account": true,
    "results_visible": false,
    "results_state": "hidden",
    "open_at": "2026-03-30T10:00:00+02:00",
    "close_at": "2026-04-02T20:00:00+02:00",
    "cta": {
      "label": "Vote Now",
      "action": "open_poll",
      "destination": "poll_detail",
      "disabled": false
    }
  },
  "options": [
    {
      "id": 11,
      "title": "Player A",
      "subtitle": "Club A",
      "description": "4 goals in 3 matches.",
      "image_url": null,
      "video_url": null,
      "thumbnail_url": null,
      "badge_text": null,
      "stats_summary": null,
      "display_order": 1,
      "entity_type": null,
      "entity_id": null,
      "is_selected": false,
      "vote_count": null,
      "percentage": null
    }
  ],
  "results": null
}
```

## Vote Success Response Shape

```json
{
  "poll": {
    "id": 4,
    "slug": "player-of-the-month",
    "type": "single_choice",
    "category": "player_of_the_month",
    "title": "Player of the Month",
    "question": "Who should win Player of the Month?",
    "description": "Vote for the standout player.",
    "status": "live",
    "open_at": "2026-03-30T10:00:00+02:00",
    "close_at": "2026-04-02T20:00:00+02:00",
    "login_required": true,
    "verified_account_required": true,
    "result_visibility_mode": "live_percentages",
    "sponsor_name": "TNBO",
    "cover_image_url": null,
    "banner_image_url": null,
    "context_type": null,
    "context_id": null
  },
  "poll_surface": {
    "title": "Player of the Month",
    "short_description": "Vote for the standout player.",
    "state": "already_voted",
    "auth_state": "verified",
    "available": false,
    "can_vote": false,
    "has_voted": true,
    "my_vote_option_id": 11,
    "requires_login": true,
    "requires_verified_account": true,
    "results_visible": true,
    "results_state": "live",
    "open_at": "2026-03-30T10:00:00+02:00",
    "close_at": "2026-04-02T20:00:00+02:00",
    "cta": {
      "label": "View Results",
      "action": "open_poll",
      "destination": "poll_detail",
      "disabled": false
    }
  },
  "options": [
    {
      "id": 11,
      "title": "Player A",
      "subtitle": "Club A",
      "description": "4 goals in 3 matches.",
      "image_url": null,
      "video_url": null,
      "thumbnail_url": null,
      "badge_text": null,
      "stats_summary": null,
      "display_order": 1,
      "entity_type": null,
      "entity_id": null,
      "is_selected": true,
      "vote_count": 1,
      "percentage": 100
    },
    {
      "id": 12,
      "title": "Player B",
      "subtitle": "Club B",
      "description": "3 goals in 3 matches.",
      "image_url": null,
      "video_url": null,
      "thumbnail_url": null,
      "badge_text": null,
      "stats_summary": null,
      "display_order": 2,
      "entity_type": null,
      "entity_id": null,
      "is_selected": false,
      "vote_count": 0,
      "percentage": 0
    }
  ],
  "results": {
    "total_votes": 1,
    "winner_option_id": 11,
    "options": [
      {
        "id": 11,
        "title": "Player A",
        "subtitle": "Club A",
        "description": "4 goals in 3 matches.",
        "image_url": null,
        "video_url": null,
        "thumbnail_url": null,
        "badge_text": null,
        "stats_summary": null,
        "display_order": 1,
        "entity_type": null,
        "entity_id": null,
        "is_selected": true,
        "vote_count": 1,
        "percentage": 100
      },
      {
        "id": 12,
        "title": "Player B",
        "subtitle": "Club B",
        "description": "3 goals in 3 matches.",
        "image_url": null,
        "video_url": null,
        "thumbnail_url": null,
        "badge_text": null,
        "stats_summary": null,
        "display_order": 2,
        "entity_type": null,
        "entity_id": null,
        "is_selected": false,
        "vote_count": 0,
        "percentage": 0
      }
    ]
  },
  "submitted_at": "2026-03-30T12:45:00+02:00"
}
```

## Results Endpoint Shape

```json
{
  "poll": {
    "id": 4,
    "slug": "player-of-the-month",
    "type": "single_choice",
    "category": "player_of_the_month",
    "title": "Player of the Month",
    "question": "Who should win Player of the Month?",
    "description": "Vote for the standout player.",
    "status": "closed",
    "open_at": "2026-03-30T10:00:00+02:00",
    "close_at": "2026-04-02T20:00:00+02:00",
    "login_required": true,
    "verified_account_required": true,
    "result_visibility_mode": "final_results",
    "sponsor_name": "TNBO",
    "cover_image_url": null,
    "banner_image_url": null,
    "context_type": null,
    "context_id": null
  },
  "poll_surface": {
    "state": "results_only",
    "auth_state": "signed_out",
    "has_voted": false,
    "my_vote_option_id": null,
    "results_visible": true,
    "results_state": "final"
  },
  "options": [
    {
      "id": 11,
      "title": "Player A",
      "subtitle": "Club A",
      "description": "4 goals in 3 matches.",
      "image_url": null,
      "video_url": null,
      "thumbnail_url": null,
      "badge_text": null,
      "stats_summary": null,
      "display_order": 1,
      "entity_type": null,
      "entity_id": null,
      "is_selected": false,
      "vote_count": 120,
      "percentage": 48
    },
    {
      "id": 12,
      "title": "Player B",
      "subtitle": "Club B",
      "description": "3 goals in 3 matches.",
      "image_url": null,
      "video_url": null,
      "thumbnail_url": null,
      "badge_text": null,
      "stats_summary": null,
      "display_order": 2,
      "entity_type": null,
      "entity_id": null,
      "is_selected": false,
      "vote_count": 130,
      "percentage": 52
    }
  ],
  "results": {
    "total_votes": 250,
    "winner_option_id": 12,
    "options": [
      {
        "id": 11,
        "title": "Player A",
        "subtitle": "Club A",
        "description": "4 goals in 3 matches.",
        "image_url": null,
        "video_url": null,
        "thumbnail_url": null,
        "badge_text": null,
        "stats_summary": null,
        "display_order": 1,
        "entity_type": null,
        "entity_id": null,
        "is_selected": false,
        "vote_count": 120,
        "percentage": 48
      },
      {
        "id": 12,
        "title": "Player B",
        "subtitle": "Club B",
        "description": "3 goals in 3 matches.",
        "image_url": null,
        "video_url": null,
        "thumbnail_url": null,
        "badge_text": null,
        "stats_summary": null,
        "display_order": 2,
        "entity_type": null,
        "entity_id": null,
        "is_selected": false,
        "vote_count": 130,
        "percentage": 52
      }
    ]
  }
}
```

## Product-Facing Surface States

Current `poll_surface.state` values:
- `signed_out`
- `verification_required`
- `available`
- `already_voted`
- `scheduled`
- `closed`
- `results_only`
- `unavailable`

Current `poll_surface.auth_state` values:
- `signed_out`
- `verified`
- `unverified`
- `unknown`

Notes:
- `unknown` means Interactive could not resolve AuthBox profile state during a read request
- if a poll requires verified accounts and auth state is `unknown`, the surface falls back to `unavailable`

## Result Visibility Rules

Implemented result modes:
- `hidden_until_end`
- `live_percentages`
- `final_results`

Current behavior:
- `hidden_until_end`: results hidden until the poll closes
- `live_percentages`: results visible while live only if `allow_result_view_before_vote = true` or after the user has voted; always visible after close
- `final_results`: results visible after close

## Current Error Codes

Common poll-specific machine-readable codes:
- `POLL_NOT_FOUND`
- `POLL_LOGIN_REQUIRED`
- `POLL_VERIFICATION_REQUIRED`
- `POLL_NOT_OPEN`
- `POLL_CLOSED`
- `POLL_ALREADY_VOTED`
- `POLL_OPTION_INVALID`
- `POLL_RESULTS_HIDDEN`
- `POLL_CONFIGURATION_ERROR`

Cross-cutting auth/service codes still apply:
- `SERVICE_UNAUTHORIZED`
- `AUTH_TOKEN_MISSING`
- `AUTH_TOKEN_INVALID`
- `AUTH_TOKEN_EXPIRED`
- `AUTHBOX_PROFILE_UNAVAILABLE`

## BFF Notes

Recommended BFF route pattern:
- `GET /api/bff/interactive/polls/summary?poll_slug=...`
- `GET /api/bff/interactive/polls/{pollSlug}`
- `POST /api/bff/interactive/polls/{pollSlug}/vote`
- `GET /api/bff/interactive/polls/{pollSlug}/results`

Recommended block/template name:
- `single_choice_poll`

Current practical usage:
- use `/summary` for Games/Home poll cards
- use `/{pollSlug}` for a dedicated poll screen when needed
- after vote submission, render directly from the vote response instead of forcing a second read
- Flutter can now reuse the same option-card model across summary, detail, vote success, and results
