# 06. Rendering and Media Guidelines

## 6.1 Goal

Make frontend rendering predictable so the app can easily support different poll editorial formats without custom code for each one.

## 6.2 Standard Rendering Model

Every single-choice poll should render as:

- poll header
- poll question
- optional description
- list/grid of selectable option cards
- vote action
- post-vote state / results state

## 6.3 Option Card Structure

Each option card should support:

- hero image or video thumbnail
- title
- subtitle
- short description
- optional badge
- optional stat summary
- selected state

## 6.4 Suggested UI Patterns by Use Case

### Team of the Week
Best as image-card grid or stacked cards.

Option card contents:
- team logo or team image
- team name
- short explanation of weekly performance
- optional stat line

### Player of the Month
Best as portrait card or stacked nominee card.

Option card contents:
- player image
- player name
- club name
- short performance summary
- optional stat badge

### Goal of the Tournament
Best as video-first card.

Option card contents:
- thumbnail image
- play icon / preview state
- scorer name
- match label
- short goal description

## 6.5 Media Rules

### Images
Recommended for:
- teams
- players
- coaches
- awards

### Videos
Recommended for:
- goal votes
- save of the week
- skill/assist of the tournament

V1 recommendation:
- store `video_url`
- optionally show thumbnail first
- actual playback behavior can be handled by app or BFF policy

## 6.6 Description Length Guidance

To keep cards readable:

- short description target: 80-180 characters
- stats summary target: 5-30 characters
- badge target: 1-3 words

Examples:
- `Two wins, six goals scored, one clean sheet.`
- `4G 2A`
- `Top scorer`

## 6.7 Recommended Result States

### Before Vote
- option cards visible
- no results unless policy allows

### After Vote
- selected option visibly marked
- voting disabled
- results shown depending on mode

### After Poll Ends
- final winner highlighted
- percentages or totals shown
- winner badge optional

## 6.8 Accessibility / Reliability Considerations

- support text-only rendering if media missing
- do not require video playback for vote submission
- make selected state obvious
- support low-bandwidth fallback using thumbnail only

## 6.9 Frontend Contract Principle

The frontend should not infer the use case from title text.
It should use structured fields:
- `type`
- `category`
- option media fields
- result mode
- vote status

That keeps rendering stable even when poll names or sponsor titles change.
