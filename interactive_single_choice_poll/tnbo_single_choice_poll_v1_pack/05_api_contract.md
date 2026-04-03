# 05. API Contract

## 5.1 General Notes

Client flow is expected to be:

Flutter App -> BFF -> Interactive API

BFF may:
- pass through auth identity
- normalize response shapes
- combine poll data with TNBO content context
- cache active polls for app home surfaces

## 5.2 Recommended Endpoints

### List Active Polls
`GET /api/v1/polls`

Suggested query params:
- `status=live`
- `category=player_of_the_month`
- `context_type=article`
- `context_id=123`
- `featured=1`

### Get Poll Detail
`GET /api/v1/polls/{id}`

### Submit Vote
`POST /api/v1/polls/{id}/vote`

### View Poll Results
`GET /api/v1/polls/{id}/results`

### Record Impression (optional)
`POST /api/v1/polls/{id}/impression`

## 5.3 Poll Detail Response Example

```json
{
  "id": "poll_001",
  "type": "single_choice",
  "category": "player_of_the_month",
  "title": "TNBO Player of the Month",
  "question": "Who should be Player of the Month?",
  "description": "Vote for the player whose performances stood out this month.",
  "status": "live",
  "open_at": "2026-04-01T08:00:00Z",
  "close_at": "2026-04-07T20:00:00Z",
  "result_visibility_mode": "live_percentages",
  "has_voted": false,
  "my_vote_option_id": null,
  "login_required": true,
  "verified_account_required": true,
  "context": {
    "type": "campaign",
    "id": "cmp_001"
  },
  "options": [
    {
      "id": "opt_001",
      "title": "Player A",
      "subtitle": "Club X",
      "description": "4 goals and 2 assists in 5 matches.",
      "image_url": "https://cdn.example.com/player-a.jpg",
      "video_url": null,
      "thumbnail_url": null,
      "badge_text": "Top scorer",
      "stats_summary": "4G 2A",
      "display_order": 1,
      "entity_type": "player",
      "entity_id": 101
    },
    {
      "id": "opt_002",
      "title": "Player B",
      "subtitle": "Club Y",
      "description": "3 goals, 3 assists, 2 clean-sheet contributions.",
      "image_url": "https://cdn.example.com/player-b.jpg",
      "video_url": null,
      "thumbnail_url": null,
      "badge_text": null,
      "stats_summary": "3G 3A",
      "display_order": 2,
      "entity_type": "player",
      "entity_id": 102
    }
  ]
}
```

## 5.4 Vote Submission Request Example

```json
{
  "option_id": "opt_001",
  "client": "android",
  "session_id": "sess_123",
  "metadata": {
    "surface": "home_featured_poll"
  }
}
```

## 5.5 Vote Submission Success Example

```json
{
  "success": true,
  "message": "Vote submitted successfully.",
  "poll_id": "poll_001",
  "option_id": "opt_001",
  "submitted_at": "2026-04-03T10:40:00Z",
  "results_available": true,
  "results": {
    "total_votes": 1200,
    "options": [
      {
        "id": "opt_001",
        "title": "Player A",
        "vote_count": 600,
        "percentage": 50.0
      },
      {
        "id": "opt_002",
        "title": "Player B",
        "vote_count": 400,
        "percentage": 33.33
      },
      {
        "id": "opt_003",
        "title": "Player C",
        "vote_count": 200,
        "percentage": 16.67
      }
    ],
    "my_vote_option_id": "opt_001"
  }
}
```

## 5.6 Results Response Example

```json
{
  "poll_id": "poll_001",
  "status": "live",
  "result_visibility_mode": "live_percentages",
  "total_votes": 1200,
  "winner_option_id": "opt_001",
  "options": [
    {
      "id": "opt_001",
      "title": "Player A",
      "vote_count": 600,
      "percentage": 50.0
    },
    {
      "id": "opt_002",
      "title": "Player B",
      "vote_count": 400,
      "percentage": 33.33
    }
  ]
}
```

## 5.7 Error Cases

### Poll Not Live
- 422 or 409
- message: `Voting is not open for this poll.`

### Already Voted
- 409
- message: `You have already voted in this poll.`

### Verification Required
- 403
- message: `A verified account is required to vote in this poll.`

### Invalid Option
- 422
- message: `Selected option is invalid for this poll.`

## 5.8 Admin Endpoints

Suggested admin routes:

- `GET /api/admin/v1/polls`
- `POST /api/admin/v1/polls`
- `PUT /api/admin/v1/polls/{id}`
- `POST /api/admin/v1/polls/{id}/publish`
- `POST /api/admin/v1/polls/{id}/close`
- `POST /api/admin/v1/polls/{id}/archive`
- `POST /api/admin/v1/polls/{id}/options`
- `PUT /api/admin/v1/polls/{id}/options/{optionId}`
- `DELETE /api/admin/v1/polls/{id}/options/{optionId}`
- `GET /api/admin/v1/polls/{id}/results`
- `GET /api/admin/v1/polls/{id}/export`
