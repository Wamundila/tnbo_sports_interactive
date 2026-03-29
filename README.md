# TNBO Sports Interactive Backend

Laravel backend for TNBO Sports Interactive trivia.

This service has two responsibilities:
- serve the protected trivia API used internally by the TNBO Sports stack
- provide a local admin web UI for staff to manage quizzes, reports, and activity

## Integration Rules

These rules are not optional for this project:
- TNBO Sports app is the only user-facing app.
- Mobile traffic should be `Flutter -> BFF -> Interactive`.
- The Interactive service must not trust plain client identity flags on their own.
- Interactive verifies AuthBox JWTs locally.
- Trusted end-user IDs come from JWT `sub` and must be `ts_*` IDs.
- Verified-account enforcement comes from AuthBox-backed profile data.
- Admin accounts are local to this app and are separate from TNBO Sports end-user accounts.

## Tech Notes

- Framework: Laravel 12
- Admin UI: plain Blade + static CSS
- Front-end build step: not required for admin UI
- Local run target: `php artisan serve`

## Local Setup

1. Install PHP dependencies.
```bash
composer install
```

2. Create your environment file.
```bash
copy .env.example .env
```

3. Generate the app key.
```bash
php artisan key:generate
```

4. Configure your database and project-specific `.env` values.

5. Run migrations and seed the default admin account.
```bash
php artisan migrate --seed
```

6. Start the app.
```bash
php artisan serve
```

Admin login will then be available at:
- `/admin/login`

## Default Seeded Admin

The project includes a simple admin seeder for local access.

Default values from `.env.example`:
- email: `admin@interactive.local`
- password: `password123`

Change these in your local `.env` before running `php artisan migrate --seed` if you do not want the defaults.

Relevant env keys:
- `ADMIN_SEED_NAME`
- `ADMIN_SEED_EMAIL`
- `ADMIN_SEED_PASSWORD`
- `ADMIN_SEED_ROLE`

## What Works Without npm

The admin UI is intentionally server-rendered.

You only need:
```bash
php artisan serve
```

You do not need:
- `npm run dev`
- `npm run build`
- Livewire

## Main Routes

### Admin Web UI
- `GET /admin/login`
- `POST /admin/login`
- `POST /admin/logout`
- `GET /admin`
- `GET /admin/quizzes`
- `GET /admin/quizzes/create`
- `GET /admin/quizzes/{quiz}/edit`
- `GET /admin/reports`

### Admin API
These are token-based admin API routes, separate from the session-based web UI.

- `POST /api/admin/auth/login`
- `GET /api/admin/auth/me`
- `POST /api/admin/auth/logout`
- `GET /api/admin/overview`
- `GET /api/admin/trivia/attempts`
- `GET /api/admin/trivia/leaderboards`
- `GET /api/admin/trivia/activity`
- `GET /api/admin/trivia/quizzes`
- `POST /api/admin/trivia/quizzes`
- `GET /api/admin/trivia/quizzes/{quiz}`
- `PUT /api/admin/trivia/quizzes/{quiz}`
- `POST /api/admin/trivia/quizzes/{quiz}/publish`
- `POST /api/admin/trivia/quizzes/{quiz}/close`
- `POST /api/admin/trivia/quizzes/{quiz}/duplicate`

### Protected Trivia API
These routes are for internal service-to-service use. They are not public mobile endpoints.

- `GET /api/v1/trivia/today`
- `POST /api/v1/trivia/today/start`
- `POST /api/v1/trivia/attempts/{attempt}/submit`
- `GET /api/v1/trivia/me/summary`
- `GET /api/v1/trivia/me/history`
- `GET /api/v1/trivia/leaderboards`

## Protected Trivia Request Requirements

Requests to `/api/v1/...` are protected by internal middleware.

Required headers:
- `Authorization: Bearer <authbox_jwt>`
- `X-TNBO-Service-Key: <shared_internal_key>` when `INTERACTIVE_SERVICE_KEY` is configured

Expected behavior:
- JWT signature is verified locally with the configured public key
- `sub` must match the configured pattern, default `^ts_\d+$`
- `iss` and `aud` are enforced if configured
- starting/submitting trivia also requires a verified account from AuthBox profile data

## Environment Variables

Below are the project-specific settings you are most likely to touch.

### Core App
- `APP_NAME`: Laravel app name used in logs and defaults.
- `APP_ENV`: environment name, usually `local`, `staging`, or `production`.
- `APP_KEY`: Laravel encryption key. Must be set.
- `APP_DEBUG`: debug mode. Keep `false` outside local.
- `APP_URL`: base app URL.

### Database
- `DB_CONNECTION`: database driver. Usually `mysql` locally for this project.
- `DB_HOST`: database host.
- `DB_PORT`: database port.
- `DB_DATABASE`: database name.
- `DB_USERNAME`: database user.
- `DB_PASSWORD`: database password.

### Sessions, Cache, Queue
- `SESSION_DRIVER`: session storage driver. The admin web UI uses sessions.
- `SESSION_LIFETIME`: admin session lifetime in minutes.
- `CACHE_STORE`: cache driver. Also used for AuthBox profile caching.
- `QUEUE_CONNECTION`: queue backend if async work is added later.

### Internal Service Security
- `INTERACTIVE_SERVICE_KEY`: shared internal key expected in `X-TNBO-Service-Key` for protected trivia API requests.

Guideline:
- set this in staging/production
- if left blank, service-key enforcement is skipped
- do not expose this to the mobile app directly

### AuthBox Profile Lookup`r`n- `AUTHBOX_BASE_URL`: base URL of the AuthBox service.`r`n- `AUTHBOX_API_KEY`: API key sent to AuthBox as `X-API-Key` for profile lookup requests.`r`n- `AUTHBOX_CURRENT_USER_PATH`: path used to fetch the current user profile, default `/api/v1/me`.`r`n- `AUTHBOX_TIMEOUT_SECONDS`: HTTP timeout for AuthBox profile lookup.`r`n- `AUTHBOX_PROFILE_CACHE_TTL_SECONDS`: cache duration for AuthBox profile responses.

Guideline:
- this is required for verified-account enforcement to work correctly`r`n- both `AUTHBOX_BASE_URL` and `AUTHBOX_API_KEY` must be configured`r`n- if either is missing, verified-profile lookups will fail

### JWT Verification
- `JWT_ALGORITHM`: expected JWT algorithm. Default is `RS256`.
- `JWT_PUBLIC_KEY`: inline public key string. Optional alternative to file path.
- `JWT_PUBLIC_KEY_PATH`: path to the public key PEM file, default `config/auth_public.pem`.
- `JWT_ISSUER`: expected `iss` claim. Optional but recommended.
- `JWT_AUDIENCE`: expected `aud` claim. Optional but recommended.
- `JWT_SUBJECT_PATTERN`: regex for trusted user IDs. Default `^ts_\d+$`.
- `JWT_CLOCK_SKEW_SECONDS`: allowed clock skew for `nbf` and `exp` checks.

Guideline:
- use either `JWT_PUBLIC_KEY` or `JWT_PUBLIC_KEY_PATH`
- `JWT_PUBLIC_KEY_PATH` is the normal local setup
- keep the subject pattern aligned to AuthBox `ts_*` IDs

### Trivia Behavior
- `TRIVIA_ATTEMPT_GRACE_SECONDS`: grace window after attempt expiry.
- `TRIVIA_LEADERBOARD_DEFAULT_LIMIT`: default leaderboard row limit.

### Admin API Tokens
- `ADMIN_TOKEN_TTL_MINUTES`: expiry window for token-based admin API logins.

This does not control the session-based admin web login directly. It controls `/api/admin/auth/login` tokens.

### Seeded Admin Access
- `ADMIN_SEED_NAME`: seeded local admin display name.
- `ADMIN_SEED_EMAIL`: seeded local admin email.
- `ADMIN_SEED_PASSWORD`: seeded local admin password.
- `ADMIN_SEED_ROLE`: seeded local admin role.

## Scheduler / Operations Commands

The app includes console commands for quiz operations.

Available commands:
- `php artisan trivia:quizzes:auto-publish`
- `php artisan trivia:quizzes:auto-close`
- `php artisan trivia:attempts:expire`
- `php artisan trivia:leaderboards:refresh`

These are also scheduled in `routes/console.php`.

## Quiz Publishing Rules

A quiz can only be published when:
- `opens_at` is before `closes_at`
- it has exactly the expected number of active questions
- each active question has exactly 3 options
- each active question has exactly 1 correct option

## MySQL Notes

Two MySQL-specific adjustments are already baked into migrations:
- trivia attempt lifecycle columns use `dateTime` instead of fragile `timestamp` combinations
- several composite indexes use explicit short names to avoid MySQL identifier length failures

If you are starting fresh locally, this is the safest reset path:
```bash
php artisan migrate:fresh --seed
```

## Testing

Run the test suite with:
```bash
php artisan test
```

## Practical Local Flow

For a normal local session:
1. `composer install`
2. `copy .env.example .env`
3. update `.env`
4. `php artisan key:generate`
5. `php artisan migrate --seed`
6. `php artisan serve`
7. open `/admin/login`

## File References

Useful places to inspect when changing behavior:
- [routes/web.php](C:/xampp/htdocs/interactive/routes/web.php)
- [routes/api.php](C:/xampp/htdocs/interactive/routes/api.php)
- [config/jwt.php](C:/xampp/htdocs/interactive/config/jwt.php)
- [config/trivia.php](C:/xampp/htdocs/interactive/config/trivia.php)
- [config/admin.php](C:/xampp/htdocs/interactive/config/admin.php)
- [config/services.php](C:/xampp/htdocs/interactive/config/services.php)
- [database/seeders/AdminSeeder.php](C:/xampp/htdocs/interactive/database/seeders/AdminSeeder.php)

