# TNBO Interactive Ã¢â‚¬â€ Single Choice Poll Module (V1)

This pack provides an implementation-oriented specification for adding a **Single Choice Poll** module to the **TNBO Interactive** Laravel application.

The module is designed to support editorial and fan-voting formats such as:

- Team of the Week
- Player of the Month
- Goal of the Tournament
- Match MVP / Player of the Match
- Best Coach of the Month
- Best Save of the Week
- Fan Favourite Jersey / Goal Celebration / Performance

The design assumes:

- **Interactive** is a standalone Laravel application with its own admin dashboard.
- User-facing interaction flows through **Flutter app -> BFF -> Interactive API**.
- Poll participation can be restricted to authenticated and optionally verified TNBO Sports accounts.
- Poll options may need **image, video, description, and metadata** so they can present nominees properly.

## Files in this pack

1. `01_product_scope.md`
2. `02_architecture.md`
3. `03_data_model.md`
4. `04_admin_dashboard_spec.md`
5. `05_api_contract.md`
6. `06_rendering_and_media_guidelines.md`
7. `07_results_and_analytics.md`
8. `08_build_checklist.md`
9. `09_system_integration_alignment.md`

## Integration Note

Read `09_system_integration_alignment.md` before implementation. Where it conflicts with the earlier pack assumptions, treat the alignment note as the source of truth.

## Recommended V1 Positioning

Build this as a **generic single-choice poll engine** inside Interactive.

That means the backend logic is reusable, but each poll instance can be configured differently:

- Team of the Week
- Player of the Month
- Goal of the Tournament
- Player of the Match

Each poll should be created and managed from the Interactive dashboard, while the Flutter app renders it using a fixed contract.

## V1 Core Principles

- One user = one vote per poll
- Standardized frontend rendering
- Rich option cards with media and descriptions
- Clear opening/closing windows
- Fast submission flow
- Transparent results state
- Flexible enough for future sponsor branding and featured placements
