# 08. Build Checklist

## 8.1 Backend Foundations

- create migrations for polls, poll_options, poll_votes
- create Eloquent models and relationships
- add enums/constants for status, type, result mode, category
- add policies/permissions for admin management

## 8.2 Admin Dashboard

- polls list page
- create/edit poll form
- option create/edit form
- option ordering UI
- publish/close/archive actions
- results screen

## 8.3 API Layer

- public/consumer poll list endpoint
- poll detail endpoint
- vote submission endpoint
- results endpoint
- admin CRUD endpoints

## 8.4 Validation Rules

- require minimum 2 active options before publish
- validate option belongs to poll on submission
- block votes outside live window
- enforce one vote per user per poll
- enforce verification requirement if enabled

## 8.5 Result Computation

- build service/repository for totals and percentages
- compute winner
- support result visibility modes
- ensure post-vote response can include allowed result data

## 8.6 Analytics

- emit or store poll view event
- emit or store vote submission event
- support aggregate reporting

## 8.7 BFF Integration

- proxy or normalize poll endpoints
- attach auth identity from AuthBox
- expose home/feature poll endpoints if needed
- ensure stable response shape for Flutter

## 8.8 Flutter / Client Considerations

- render poll cards consistently
- support image-first and video-first options
- show locked state for unauthenticated/unverified user
- show already-voted state clearly
- show results according to visibility mode

## 8.9 Recommended V1 Acceptance Criteria

### Admin
- admin can create a poll with title, question, timing, and result mode
- admin can add at least 2 options with media and descriptions
- admin can publish and close a poll
- admin can view result summaries

### User
- eligible user can open poll detail
- eligible user can select one option and submit
- duplicate voting is blocked
- user sees correct post-vote state
- results render according to configured visibility mode

### Product
- same engine works for Team of the Week, Player of the Month, and Goal of the Tournament
- no frontend custom rendering logic is needed per individual poll instance beyond category-aware presentation

## 8.10 Recommended Implementation Sequence

1. database schema
2. models and relationships
3. admin CRUD
4. vote submission service
5. results service
6. public APIs
7. BFF integration
8. Flutter rendering
9. analytics and reporting
10. QA with real editorial sample polls
