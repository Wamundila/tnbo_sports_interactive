# 02. Architecture

## 2.1 System Position

The Single Choice Poll module lives inside the **Interactive Laravel app**.

### Request Flow

Flutter App -> BFF -> Interactive API -> Interactive Database

### Admin Flow

Admin User -> Interactive Dashboard -> Interactive Database

## 2.2 Design Principle

Build this as a **generic poll engine** with a standardized type of `single_choice`.

Do not build separate hardcoded modules for:
- Team of the Week
- Player of the Month
- Goal of the Tournament

Those should all be poll instances using the same engine.

## 2.3 Main Components

### Poll Management
Handles poll creation, publishing, editing, closing, status changes.

### Poll Options Management
Handles nominees/options, ordering, media, descriptions, metadata.

### Voting Service
Validates eligibility and records the selected option.

### Results Service
Computes totals, percentages, winner, and result presentation state.

### Analytics Service
Captures impressions, option clicks, vote submissions, share actions, etc.

## 2.4 Domain Model Overview

### Poll
Top-level unit representing one voting experience.

### Poll Option
One selectable nominee/choice under a poll.

### Poll Vote
One user's final vote for one option in one poll.

### Poll Context
Optional relation tying the poll to an article, match, tournament, or other content.

## 2.5 Recommended Poll Lifecycle

Draft -> Scheduled -> Live -> Closed -> Archived

### Draft
Admin is still preparing the poll.

### Scheduled
Poll is ready and has a future open time.

### Live
Users can vote.

### Closed
Voting no longer allowed. Results may now be shown depending on configuration.

### Archived
Old poll retained for reference/history.

## 2.6 Permissions / Roles

Suggested admin permissions:

- `polls.view`
- `polls.create`
- `polls.edit`
- `polls.publish`
- `polls.close`
- `polls.delete`
- `polls.view_results`
- `polls.export`

## 2.7 Media Handling Strategy

Each poll option should support structured media fields rather than one generic blob only.

Recommended support:
- image
- video
- thumbnail
- caption
- stat summary

Media can be stored as URLs referencing TNBO media storage/CDN.

## 2.8 Voting Integrity Rules

At minimum:
- one vote per eligible user per poll
- vote only while poll is live
- vote only for an option that belongs to the poll
- prevent duplicate vote submissions

Recommended V1 behavior:
- once submitted, vote is final
- edits are not allowed in V1

## 2.9 Future Compatibility

The poll engine should be built so future types can reuse common tables and services.

Possible future types:
- multi_choice
- head_to_head
- rating
- score_prediction

That means the code structure should separate:
- common poll entity behavior
- type-specific validation/rendering rules

Even if V1 only enables `single_choice`, the internal design should avoid dead-end assumptions.
