# TNBO Interactive Project Overview

This note summarizes the current implementation state of the TNBO Interactive backend, covering the Daily Trivia module and the Predictor League module.

It also lists the recommended next work for each module.

## System Context

This service is the TNBO Interactive backend for game features inside the TNBO Sports app.

Core integration rules already implemented across the project:
- TNBO Sports app is the only user-facing app.
- Expected path is `Flutter -> BFF -> Interactive`.
- Interactive verifies AuthBox JWTs locally.
- Trusted user IDs are `ts_*` from JWT `sub`.
- Interactive resolves verified-account state from AuthBox-backed profile lookup.
- Protected service-to-service access supports `X-TNBO-Service-Key`.
- End-user gameplay identity does not come from local admin auth or client-declared booleans.

## Daily Trivia Module

### Current Status

Trivia is implemented and usable end-to-end for the current scope.

### What Is Already Done

User-facing backend:
- protected trivia APIs under `/api/v1/trivia`
- `GET /today`
- `GET /summary`
- `POST /today/start`
- `POST /attempts/{attempt}/submit`
- `GET /me/summary`
- `GET /me/history`
- `GET /leaderboards`
- local AuthBox JWT verification
- verified-account enforcement for play actions
- `ts_*` user-id persistence
- active-attempt handling and attempt expiry
- scoring, streaks, profile updates, and leaderboard refresh
- machine-readable error codes

Admin/backend:
- local admin auth for Interactive staff
- admin quiz CRUD
- question and option management
- quiz publish, close, and duplicate actions
- admin reports and activity views
- scheduler/command support for quiz publish/close, expiry, and leaderboard refresh
- plain Blade admin UI that works with `php artisan serve`
- admin help/how-to guidance for making trivia live
- admin seeder for a default admin account

Documentation already present:
- `README.md`
- `integration_notes.md`
- dated trivia contract change note(s) in `interactive_notes`

### Trivia Gaps / Suggested Next Work

Recommended next work for trivia:
- admin reporting expansion
  - question-level performance
  - answer distribution
  - export-friendly reporting
- richer dashboard analytics
  - completion rate trends
  - streak trends
  - top-performing quizzes/questions
- operational hardening
  - more scheduler/ops visibility
  - admin-level run-history or job logs
- BFF/mobile contract refinements only if requested by consuming teams

Lower-priority optional work:
- badges/achievements, if the product wants to use the existing `new_badges` placeholder later
- more editorial tooling around question sourcing/explanations
- more nuanced quiz configuration if trivia formats expand beyond the current daily flow

### Trivia Recommendation

Trivia is in a stable place for the current V1 scope.

If new work is chosen here, it should mostly be reporting, analytics, or editorial/admin improvements rather than core gameplay changes.

## Predictor League Module

### Current Status

Predictor is implemented for the shared/public league flow and is now usable end-to-end for:
- campaign setup
- season setup
- round setup
- fixture management
- user picks
- scoring
- leaderboard refresh

Private leagues are not implemented yet.

### What Is Already Done

User-facing backend:
- protected predictor APIs under `/api/v1/predictor`
- `GET /campaigns`
- `GET /summary`
- `GET /campaigns/{campaign}/current-round`
- `GET /campaigns/{campaign}/leaderboards/{boardType}`
- `GET /rounds/{round}/my-entry`
- `POST /rounds/{round}/draft`
- `POST /rounds/{round}/submit`
- `GET /me/performance`
- `GET /me/history`
- local AuthBox JWT verification
- verified-account enforcement for draft/submit
- product-facing summary state values
- summary fallback `state=unavailable`
- leaderboard preview support
- scored `my-entry` rows now include actual result scores and points breakdown

Predictor data model/backend:
- campaigns
- seasons
- rounds
- round fixtures
- user round entries
- predictions
- leaderboard entries
- support for public campaign/season/round resolution
- banker validation and banker scoring
- scoring rules for outcome, exact score, close score
- postponed/cancelled fixtures treated as void during scoring
- round, monthly, season, and all-time leaderboard refresh

Admin/backend:
- sidebar-based predictor admin area in the plain Blade admin UI
- campaign CRUD
- season CRUD
- round CRUD
- fixture entry/editing
- manual result entry on fixtures
- manual round scoring
- round recalculation after corrections

Documentation already present:
- `predictor_integration_notes.md`
- predictor spec pack and follow-up notes under `interactive_predictor_leagues`

### Predictor Gaps / Recommended Next Work

#### Highest-Priority Recommendation

Private leagues.

This is the biggest missing product feature if the predictor experience is expected to become socially compelling.

Recommended private-league scope:
- create private league
- join private league via invite code
- private league membership table and roles
- private league leaderboard scope
- private league entry visibility rules
- private league admin/owner controls
- BFF/mobile contract additions for create/join/list/view-leaderboard flows

Why this is the strongest next step:
- the shared/public predictor core is already in place
- private leagues are the clearest feature expansion users will feel directly
- the schema guidance already anticipated this as the next layer

#### Other Strong Predictor Next Work

1. Match Center integration
- import/sync fixtures from Match Center
- use upstream `source_fixture_id`, `competition_id`, and team ids consistently
- sync results automatically instead of manual-only result entry
- reduce admin manual fixture creation overhead

2. Scheduled/background scoring automation
- auto-detect score-ready rounds
- scheduled scoring/recalculation jobs
- audit logging around scoring runs
- guardrails for partial/failing automation runs

3. Predictor admin reporting
- round participation counts
- submission-rate reporting
- scoring anomaly views
- leaderboard inspection tools
- result/scoring audit views

4. Predictor contract hardening for BFF/mobile
- additional dashboard/read models if Flutter surfaces expand
- richer leaderboard metadata if needed
- result explanations if product wants a more detailed scored-entry UX

### Predictor Recommendation

If only one major next feature is chosen, private leagues is the recommended product-facing next step.

If the priority is operational maturity instead of feature expansion, then Match Center integration plus automated scoring is the recommended next step.

## Suggested Priority Options

### Option A: Product-Led Next Step
- build private leagues for predictor
- keep trivia mostly stable
- only make trivia changes if another consuming team requests them

### Option B: Operations-Led Next Step
- integrate Match Center fixture/result sync for predictor
- add scheduled/background predictor scoring
- add predictor scoring/reporting admin views

### Option C: Admin/Insights Next Step
- expand trivia reporting and analytics
- expand predictor admin reporting and anomaly tooling
- postpone private leagues until the core operations layer is more mature

## Current Recommendation

Most sensible default next step:
- Predictor: private leagues
- Predictor after that: Match Center sync and scoring automation
- Trivia: reporting/analytics improvements only as needed

That recommendation assumes the goal is to expand user-visible value next, not just backend operations.
