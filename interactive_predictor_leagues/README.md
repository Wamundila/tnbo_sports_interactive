# TNBO Interactive — Predictor League V1 Implementation Pack

This pack provides implementation guidance for adding a **Predictor League** module to the TNBO Interactive Laravel app.

## Recommended Direction

Build **one reusable Predictor League engine** inside Interactive, but allow it to host **many predictor campaigns**.

Examples:
- Super League Predictor
- ABSA Cup Predictor
- TNBO Tournament Predictor

For V1, launch with **one single-competition campaign first**, while keeping the engine ready for future multi-competition use.

## Why this direction

This keeps:
- business separation for sponsorship, branding, and reporting
- one shared codebase for prediction logic, scoring, deadlines, leaderboards, and private leagues
- easier maintenance for Laravel, BFF, and Flutter

## What this pack includes

1. `01_product_scope.md`
2. `02_architecture.md`
3. `03_data_model.md`
4. `04_admin_dashboard.md`
5. `05_api_contract.md`
6. `06_scoring_and_leaderboards.md`
7. `07_bff_flutter_integration.md`
8. `08_build_checklist.md`

## V1 Product Shape

- 1 predictor engine
- 1 public predictor campaign to start
- 1 season
- rounds with flexible fixture count
- 4 featured fixtures per round by default
- score predictions for each fixture
- optional banker pick
- public leaderboards: round, monthly, season/all-time
- personal stats
- admin-managed rounds and fixtures
- BFF-mediated app access
- verified TNBO Sports account required to participate

## Suggested Naming

Technical module name:
- `predictor`

Example campaign slugs:
- `super_league_predictor`
- `absa_cup_predictor`
- `tnbo_sports_predictor`

## Notes

This pack assumes:
- the Interactive app is a separate Laravel service
- TNBO Sports app talks to Interactive via BFF
- authentication comes from AuthBox / TNBO account flow
- user-facing play happens through APIs
