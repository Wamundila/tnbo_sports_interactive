# 03. Data Model

## 3.1 Main Tables

Recommended V1 tables:

- `polls`
- `poll_options`
- `poll_votes`
- `poll_impressions` (optional for analytics)
- `poll_aggregates_daily` (optional summary table)

## 3.2 polls

Represents one poll.

Suggested fields:

- `id`
- `uuid`
- `type` default `single_choice`
- `category` nullable
- `title`
- `question`
- `slug`
- `description` nullable
- `status` (`draft`, `scheduled`, `live`, `closed`, `archived`)
- `open_at` nullable
- `close_at` nullable
- `result_visibility_mode`
- `login_required` boolean
- `verified_account_required` boolean
- `allow_result_view_before_vote` boolean
- `context_type` nullable
- `context_id` nullable
- `sponsor_name` nullable
- `cover_image_url` nullable
- `banner_image_url` nullable
- `metadata_json` nullable
- `published_at` nullable
- `created_by`
- `updated_by`
- timestamps
- soft deletes

### Notes

- `title` can be the internal/editorial display title.
- `question` is the actual voting prompt.
- `category` helps group use cases like player/team/goal awards.

## 3.3 poll_options

Represents one selectable nominee/option under a poll.

Suggested fields:

- `id`
- `uuid`
- `poll_id`
- `title`
- `subtitle` nullable
- `description` nullable
- `image_url` nullable
- `video_url` nullable
- `thumbnail_url` nullable
- `media_type` nullable (`image`, `video`, `mixed`)
- `display_order` integer
- `is_active` boolean
- `badge_text` nullable
- `stats_summary` nullable
- `entity_type` nullable (`team`, `player`, `goal`, `coach`, `generic`)
- `entity_id` nullable
- `metadata_json` nullable
- timestamps

### Use Examples

#### Player of the Month
- `entity_type = player`
- `entity_id = 123`
- `title = Patson Daka`
- `description = 4 goals, 2 assists in 5 matches`
- `image_url = ...`

#### Goal of the Tournament
- `entity_type = goal`
- `video_url = ...`
- `thumbnail_url = ...`
- `description = Long-range strike vs Team X in semifinal`

#### Team of the Week
- `entity_type = team`
- `title = Power Dynamos`
- `description = Two wins, clean sheet, dominant attack`

## 3.4 poll_votes

Stores each user's selected option.

Suggested fields:

- `id`
- `uuid`
- `poll_id`
- `poll_option_id`
- `user_id`
- `authbox_user_id` nullable
- `session_id` nullable
- `client` nullable (`android`, `ios`, `web`)
- `ip_address` nullable
- `user_agent` nullable
- `submitted_at`
- `metadata_json` nullable
- timestamps

## 3.5 Constraints

Recommended constraints:

- unique index on (`poll_id`, `user_id`)
- foreign key poll option must belong to the same poll
- poll must be live during submission

If `user_id` is local Interactive user mapping, keep it consistent.
If user identity comes from AuthBox and BFF, make sure one stable user identifier is persisted.

## 3.6 Optional Analytics Tables

### poll_impressions
Tracks exposure and view behavior.

Suggested fields:
- `id`
- `poll_id`
- `user_id` nullable
- `anon_id` nullable
- `session_id` nullable
- `client`
- `source_surface` nullable
- `event_time`

### poll_aggregates_daily
For dashboard reporting.

Suggested fields:
- `id`
- `poll_id`
- `date`
- `impressions`
- `votes`
- `unique_voters`
- `shares`
- `created_at`
- `updated_at`

## 3.7 Metadata Recommendations

`metadata_json` can be used for flexible display content such as:

- player club name
- team logo URL
- short stat labels
- match reference IDs
- tournament stage
- sponsor notes

Example for a goal option:

```json
{
  "scorer_name": "Player A",
  "match_label": "Team A vs Team B",
  "tournament_stage": "Semi Final",
  "minute": 78
}
```

## 3.8 Suggested Eloquent Models

- `Poll`
- `PollOption`
- `PollVote`
- `PollImpression`
- `PollAggregateDaily`

## 3.9 Suggested Relationships

### Poll
- hasMany `options`
- hasMany `votes`

### PollOption
- belongsTo `poll`
- hasMany `votes`

### PollVote
- belongsTo `poll`
- belongsTo `option`
