# TNBO Interactive Laravel App - Daily Trivia V1 Implementation Pack

This pack provides implementation guidance for the **first feature** in the new **TNBO Interactive** Laravel app: a **Daily Trivia Game** with points, streaks, and leaderboards.

## Read This First

Before implementation starts, also read:

- `tnbo_interactive_trivia_v1_pack/09_system_integration_alignment.md`

That addendum updates the pack with system-level integration rules based on the TNBO services that now already exist.

## Scope

This pack assumes the following TNBO services already exist:

- **AuthBoxx** for authentication and account verification
- **BFF** (Backend for Frontend) as the gateway between Flutter and backend services
- **News** service
- **Match Center** service

Important boundary:

- the end-user experience remains inside the existing TNBO Sports app
- TNBO Interactive is a backend Laravel service for game features
- users should not be modeled as using a separate standalone trivia app in V1

## Core Product Decisions

- Interactive will be its **own Laravel app**.
- Interactive will have its **own admin dashboard**.
- Interactive will expose **APIs** for gameplay and user-facing activity.
- Flutter app traffic flows as:

```text
Flutter App -> BFF -> Interactive App
```

- Only users with a **verified TNBO Sports account** may participate.
- The first implemented feature is **Daily Trivia**.
- Daily Trivia is a **once-per-day quiz** with:
  - 3 questions per day
  - 3 options per question
  - 30 seconds per question
  - 3 points per correct answer
  - leaderboards
  - streaks

## Files in this pack

- `01_product_and_scope.md` - product shape and V1 boundaries
- `02_system_architecture.md` - service boundaries, auth flow, and app responsibilities
- `03_data_model_and_schema.md` - recommended tables and model structure
- `04_admin_dashboard_spec.md` - dashboard sections and workflows
- `05_api_contract.md` - endpoints, request/response examples, and validation rules
- `06_game_rules_scoring_and_leaderboards.md` - scoring, streaks, ranking logic
- `07_backend_build_tasks.md` - implementation checklist for the Laravel team
- `08_flutter_bff_integration_notes.md` - BFF and app rendering guidance
- `09_system_integration_alignment.md` - system-alignment overrides for auth, BFF, user ids, verification, and response shaping

## Suggested V1 mindset

Keep V1 narrow and reliable:

- no custom quiz builders for end users
- no complex timed tournaments
- no friend leagues yet unless already easy in your ecosystem
- no reward redemption logic yet
- no ads inside the trivia flow
- no dependence on real-time websockets

The goal is to ship a **clean daily ritual game** that is easy to manage, fair to score, and extensible into a broader TNBO Interactive platform later.
