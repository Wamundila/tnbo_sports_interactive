# 01. Product Scope

## 1.1 Objective

Introduce a **Single Choice Poll** feature under TNBO Interactive that allows admins to create opinion or award-style voting experiences where a user selects **one nominee / option** from a set.

The module should be generic enough to support multiple editorial use cases while remaining easy for Flutter and web clients to render consistently.

## 1.2 Example Use Cases

### Team of the Week
Users vote for one team from a curated list of teams that performed well in the week.

### Player of the Month
Users vote for one nominated player. Each option may include a player image, a short summary of form, and optional stat highlights.

### Goal of the Tournament
Users vote for one goal clip. Each option may include:
- thumbnail image
- short video clip URL
- scorer name
- match reference
- short description

### Player of the Match
Fast vote tied to a specific match.

## 1.3 Why Single Choice First

This is a good next module because it is:

- easy for users to understand
- quick to play
- editorially flexible
- strong for fan engagement
- useful for sponsor integration
- reusable across many sports content contexts

## 1.4 V1 Functional Scope

### Included in V1

- create and manage single choice polls
- create rich poll options with image and/or video
- add option description text
- support poll opening and closing times
- support optional result visibility rules
- support authenticated participation
- support optional verified-account-only participation
- one vote per user per poll
- public poll result summaries
- admin dashboard to create, edit, publish, close, and review polls
- API endpoints for list, detail, submit vote, and view results
- analytics events and aggregate counts

### Not Required for V1

- ranked voting
- multi-choice voting
- bracket voting
- moderation queues for user-submitted nominees
- paid voting / premium voting
- weighted voting
- AI-generated nomination suggestions
- comments under poll options

## 1.5 Poll Types Supported by Same Engine

Although this is a single-choice module, the system should allow a poll to be categorized for business/editorial purposes.

Examples:
- `team_of_the_week`
- `player_of_the_match`
- `player_of_the_month`
- `goal_of_the_tournament`
- `fan_favourite`
- `editorial_award`

This is not a rendering change. It is mainly for admin organization, analytics, and future templates.

## 1.6 User Experience Goals

The experience should feel:

- quick
- visual
- trustworthy
- exciting
- easy to compare nominees

Key UX expectations:

- user can understand the poll question immediately
- each option card has enough media/context to make the vote feel informed
- vote submission is fast
- user clearly sees whether they have already voted
- result state is clear: hidden, partial, or final

## 1.7 Suggested Participation Rules

Recommended default for TNBO:

- user must be authenticated through TNBO Sports account
- user must have a verified account for eligibility-sensitive polls
- one vote per user per poll
- anonymous view allowed for some polls, but not vote submission

This should be configurable per poll:

- `login_required`
- `verified_account_required`
- `region_restriction` (future)
- `max_votes_per_user` (default = 1, fixed in V1)

## 1.8 Suggested Result Visibility Modes

Allow one of the following modes per poll:

### Hidden Until Poll Ends
Users do not see vote distribution until the poll closes.

### Live Percentages
Users see live percentages immediately after voting.

### Vote Count Only
Users see raw vote totals without percentages.

### Final Winner Only
Users see only the top option when the poll ends.

Recommended V1 values:
- `hidden_until_end`
- `live_percentages`
- `final_results`

## 1.9 Content Attachments / Contexts

A poll may optionally be attached to a TNBO object for placement and discovery.

Examples:
- article
- match
- tournament
- team
- player
- campaign

Suggested fields:
- `context_type`
- `context_id`

This lets BFF or Flutter show the poll in the right place, such as:
- on an article page
- on a match page
- in a tournament section
- on the app home feed
