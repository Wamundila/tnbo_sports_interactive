
# 09. System Integration Alignment Addendum

This note should be read before implementation.

It updates the trivia pack so the new Interactive app fits the TNBO system that already exists across:

- AuthBox
- BFF
- News
- Match Center
- Flutter app auth/account flows

If this note conflicts with older implementation wording in the pack, this note should take precedence for integration-sensitive decisions.

## 1. Non-Negotiable Integration Rules

### 1.1 Client Traffic Shape

The only end-user app surface for V1 trivia is the existing TNBO Sports app.

Interactive is its own Laravel backend service, but it is not a separate consumer-facing app for users.

The TNBO Sports app must not call Interactive directly.

The intended request path is:

```text
Flutter App -> BFF -> Interactive App
```

This is already the direction used elsewhere in the system and should remain true for trivia and future interactive features.

Important product boundary:

- News, Match Center, Account, Interests, and Trivia all live under the TNBO Sports app experience
- Interactive exists behind the scenes as the backend service for game features
- do not design V1 as if users install or open a separate trivia app

### 1.2 AuthBox JWT Verification

Interactive should verify AuthBox JWTs locally.

Do not rely only on:

- Flutter-declared identity
- Flutter-declared verification status
- BFF-provided plain `user_context` flags without cryptographic trust

Reuse the same pattern already implemented in BFF:

- `config/auth_public.pem`
- `config/jwt.php`
- `jwt.auth` middleware alias
- `JwtService`
- `VerifyJwtToken` middleware

The trusted TNBO user id is the AuthBox token subject:

```text
sub = ts_<number>
```

All sample user ids in the older trivia notes that look like `usr_123` should be treated as outdated.

Use:

```text
ts_123
```

### 1.3 Verified-Account Enforcement

Trivia participation is verified-user-only.

The Interactive service should not trust a raw `is_verified` boolean sent by the mobile app.

Recommended enforcement:

1. BFF forwards the end-user bearer token to Interactive.
2. Interactive verifies the JWT locally.
3. Interactive resolves the current user profile from AuthBox when needed, especially for participation gates and display snapshots.
4. Interactive checks the verification state from AuthBox profile data, for example `email_verified_at != null`.

If you add a short-lived cached user profile lookup in Interactive, that is fine.
The source of truth should still be AuthBox-backed profile data, not client-declared state.

### 1.4 BFF To Interactive Trust

BFF is the gateway, but Interactive should still protect its BFF-facing API surface.

Recommended options:

- deploy Interactive behind private/internal network access, or
- require an internal service header such as `X-TNBO-Service-Key` in addition to forwarded user auth, or
- use both

Do not expose Interactive as a public mobile-facing service.

## 2. What Already Exists In BFF

The BFF already has an AuthBox bridge for app auth flows.

That means Flutter is already expected to use BFF routes for:

- auth code exchange
- current user `/me`
- profile update
- verification email resend
- password reset OTP
- interests catalog and user interests

Interactive should not try to replace those app auth/account flows.
Interactive should focus on interactive gameplay APIs.

## 3. Data Model Adjustments Recommended For Proper Integration

### 3.1 User Identity Columns

Use AuthBox public ids directly.

Examples:

- `trivia_attempts.user_id` should store values like `ts_1`
- `user_trivia_profiles.user_id` should store values like `ts_1`
- `leaderboard_entries.user_id` should store values like `ts_1`

### 3.2 Lightweight User Snapshots

Leaderboards and quiz history need display-friendly user data.

Do not make Interactive depend on a local users table as the source of truth.

Recommended additions:

- `display_name_snapshot` on `trivia_attempts`
- `avatar_url_snapshot` on `trivia_attempts`
- `display_name_snapshot` on `leaderboard_entries`
- `avatar_url_snapshot` on `leaderboard_entries`

Alternative:

- store the current display snapshot on `user_trivia_profiles`
- materialize it into leaderboard responses when generating or refreshing leaderboard rows

The key point is that leaderboard APIs should not return only raw `ts_*` ids.

### 3.3 Attempt Expiry

Even if question timers are mostly a client UX concern, the backend should still enforce a hard attempt lifetime.

Recommended addition:

- add `expires_at` to `trivia_attempts`, or
- derive it consistently from `started_at + total_allowed_seconds + small_grace_window`

This avoids accepting obviously stale or replayed attempt submissions.

### 3.4 Optional Future-Friendly Topic Fields

If cheap to add now, consider:

- `sport_slug` nullable on `trivia_quizzes`
- `sport_slug` nullable on `trivia_questions`

Reason:

- future alignment with AuthBox interests
- future themed quizzes
- future personalized trivia surfaces in BFF

This is optional for V1 and should not block delivery.

## 4. API Contract Adjustments

### 4.1 Keep Interactive APIs Service-Oriented

The internal Interactive app routes can stay under:

```text
/api/v1/trivia
```

But those are service-facing routes for BFF, not public mobile routes.

The BFF should later expose its own app-safe routes for trivia.

### 4.2 Start Attempt Response

The start response should include enough state for the BFF and app to reason safely about the attempt.

Recommended additions:

- `attempt_id`
- `status`
- `started_at`
- `expires_at`
- `already_played`
- `requires_verified_account`

If the API returns all 3 questions immediately, keep that explicit and stable.
If it returns one question at a time, make that an explicit API design instead of an accidental partial payload.

### 4.3 Submit Attempt Rules

Submission should validate all of the following server-side:

- attempt belongs to current `ts_*` user
- attempt is not already submitted
- attempt is not expired
- quiz is open/submittable
- each option belongs to the referenced question
- each question belongs to the referenced quiz
- duplicate question answers in payload are rejected

### 4.4 Response Shape For Leaderboards

Leaderboard responses should return a user object suitable for direct BFF/mobile rendering.

Recommended shape:

```json
{
  "rank": 1,
  "user": {
    "user_id": "ts_11",
    "display_name": "John D.",
    "avatar_url": null
  },
  "points": 12,
  "accuracy": 100,
  "quizzes_played": 1
}
```

Use `ts_*` ids consistently.

### 4.5 Error Code Stability

Keep stable machine-readable codes because BFF and Flutter will map them into app UX states.

Recommended codes:

- `TRIVIA_VERIFICATION_REQUIRED`
- `TRIVIA_ALREADY_PLAYED`
- `TRIVIA_NOT_OPEN`
- `TRIVIA_CLOSED`
- `TRIVIA_ATTEMPT_EXPIRED`
- `TRIVIA_ATTEMPT_NOT_FOUND`
- `TRIVIA_INVALID_ANSWER_PAYLOAD`

## 5. BFF Integration Recommendations

The BFF should eventually own the app-facing trivia routes, for example:

- `GET /api/bff/interactive/trivia/today`
- `POST /api/bff/interactive/trivia/today/start`
- `POST /api/bff/interactive/trivia/attempts/{attemptId}/submit`
- `GET /api/bff/interactive/trivia/me/summary`
- `GET /api/bff/interactive/trivia/leaderboards`
- `GET /api/bff/interactive/trivia/me/history`

Why:

- consistent auth handling at the gateway
- consistent app-safe error mapping
- room for small payload shaping without coupling Flutter to internal service contracts
- clean place for future composition with News, Match Center, and interests
- keeps trivia embedded inside the TNBO Sports app rather than creating a separate app-facing service contract

## 6. Admin Dashboard Recommendations

Interactive should have its own admin dashboard, but keep end-user auth and admin auth separate.

Recommended rule:

- AuthBox for end-user gameplay identity
- local/internal admin auth for staff dashboard access

Do not force dashboard staff to authenticate as normal end users for operational access unless there is a deliberate cross-system SSO plan.

## 7. Build Task Changes Recommended

The existing build task list should effectively gain a new early phase.

### Phase 0 - Integration Foundation

Before quiz CRUD and gameplay logic, implement:

- JWT verification using AuthBox public key
- `config/auth_public.pem` support
- `config/jwt.php`
- `jwt.auth` middleware alias
- small AuthBox client for current-user profile lookup
- verified-account gate for gameplay endpoints
- optional internal BFF-to-Interactive service key or private network enforcement
- structured API error codes
- request/response logging for protected gameplay endpoints

### Testing additions

Add tests for:

- valid AuthBox JWT accepted
- invalid/expired JWT rejected
- unverified user blocked from trivia start
- verified user can start
- expired attempt cannot be submitted
- leaderboard output includes display-friendly user snapshot fields
- `ts_*` user ids are used consistently in persisted records and responses

## 8. Flutter-Facing Product Alignment

The Flutter app already has:

- hosted AuthBox login
- BFF auth bridge
- account profile flow
- interests onboarding and management flow

Trivia should integrate into that existing app shape:

- if user is not authenticated, trivia CTA should route to sign-in
- if user is authenticated but not verified, trivia CTA should route to verification flow
- trivia should not create a second profile or identity model
- future trivia personalization can later use AuthBox interests, but that should not block V1
- trivia should be presented as a feature inside the TNBO Sports app, not as a standalone app experience

## 9. Recommended Implementation Mindset

Build Interactive as a real TNBO service, not a standalone side project.

That means:

- trust AuthBox for identity
- trust the BFF as the mobile gateway
- keep Interactive focused on gameplay, scoring, attempts, and leaderboards
- shape APIs so BFF and Flutter need minimal cleanup
- keep user ids, auth rules, and profile conventions consistent with the rest of the TNBO system
