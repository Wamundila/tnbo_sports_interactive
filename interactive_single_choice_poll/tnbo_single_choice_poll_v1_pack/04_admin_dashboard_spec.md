# 04. Admin Dashboard Spec

## 4.1 Goal

Allow TNBO admins to create and manage polls without developer involvement.

## 4.2 Main Dashboard Sections

### Polls List
Shows all polls with filters.

Recommended columns:
- title
- category
- type
- status
- open_at
- close_at
- votes_count
- context
- created_by

Filters:
- status
- category
- date range
- context type
- sponsor

### Poll Create / Edit Screen
Core admin form for one poll.

### Poll Options Manager
Manage nominees/options within the poll.

### Results Screen
View totals, winner, percentages, exportable data.

## 4.3 Poll Create / Edit Fields

Suggested form fields:

- poll title
- poll question
- category
- description
- cover image
- context type
- context id
- open datetime
- close datetime
- result visibility mode
- login required toggle
- verified account required toggle
- sponsor name
- status

## 4.4 Option Create / Edit Fields

Each option should support:

- title
- subtitle
- description
- image upload / image URL
- video URL
- thumbnail URL
- display order
- badge text
- stats summary
- entity type
- entity id
- active toggle

## 4.5 Recommended Editor UX

### Drag-and-Drop Ordering
Allow admins to reorder poll options.

### Preview Panel
Show how the poll and options may look on mobile.

### Validation Hints
Examples:
- warn if live poll has fewer than 2 active options
- warn if close time is before open time
- warn if option has no media for media-heavy poll categories

## 4.6 Bulk Operations

Nice-to-have but useful:
- publish selected polls
- close selected polls
- archive selected polls
- export vote summaries

## 4.7 Results Screen

Recommended display:
- total votes
- option ranking
- percentages
- winner
- time series chart of votes over time
- filter by client/platform if tracked

## 4.8 Auditability

Recommended logging:
- who created poll
- who edited poll
- when it was published
- when it was closed

This matters for editorial and sponsor-facing trust.

## 4.9 Suggested V1 Admin Workflow

1. Create draft poll
2. Add options with media and descriptions
3. Preview
4. Schedule or publish
5. Monitor participation
6. Close poll
7. Review/export results
