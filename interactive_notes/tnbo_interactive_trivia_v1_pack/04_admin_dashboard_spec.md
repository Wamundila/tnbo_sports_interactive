# 04. Admin Dashboard Spec

## Goal

The dashboard should allow TNBO staff to manage Daily Trivia without developer intervention.

## Main dashboard sections

### 1. Overview

Cards:

- today's quiz status
- attempts today
- average score today
- current active streak leader
- top 5 on today's leaderboard
- pending draft quizzes

### 2. Trivia Quizzes

List view columns:

- quiz date
- title
- status
- questions count
- opens at
- closes at
- attempts count
- avg score
- actions

Actions:

- create
- edit
- duplicate from previous day
- publish
- close
- archive
- preview

### 3. Trivia Question Builder

Inside a quiz:

- quiz metadata block
- list of questions by position
- add question
- reorder question positions if needed
- add optional image
- add explanation
- choose source type
- add exactly 3 options
- mark one correct option

## Validation rules in dashboard

- quiz date required and unique
- exactly 3 active questions required for publish
- each question must have exactly 3 options
- each question must have exactly 1 correct option
- opens_at must be before closes_at
- quiz cannot be published if invalid

### 4. Attempts / Participation

Filters:

- by date
- by score
- by completion status
- by client

Columns:

- user_id
- display_name if available
- score
- correct answers
- streak after
- started at
- submitted at
- total time

### 5. Leaderboards

Tabs:

- daily
- weekly
- monthly
- all-time

Filters:

- date/period
- top N

Columns:

- rank
- user
- points
- quizzes played
- accuracy
- average score

### 6. Reports / Exports

Exports for:

- attempts per date
- question performance
- leaderboard snapshots
- low-performing questions

### 7. Admin Audit Trail

Useful events:

- quiz created
- quiz updated
- quiz published
- quiz closed
- question changed
- answer corrected

## Suggested admin workflows

### Workflow A: Prepare next 7 days

1. Create quizzes for next 7 dates
2. Add 3 questions each
3. Save as drafts
4. Review wording and answers
5. Publish on schedule

### Workflow B: Emergency correction before start

1. Open quiz
2. Edit question or option
3. Save
4. Republish if required

### Workflow C: Review performance

1. Open completed quiz
2. See participation
3. See question-level correctness rate
4. Identify too-easy or too-hard questions

## Dashboard UX note

Do not overcomplicate the first admin build.

A clean CRUD dashboard with good validation is more valuable than visual flourish.
