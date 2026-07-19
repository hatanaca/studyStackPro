# Lua Integration Technical Documentation

## 1. Purpose

This document records, in a technical and secure manner, the Lua integration performed in `StudyTrack Pro`.

The focus here is not just listing changed files, but explaining:

- the problem each change solved;
- how Redis Lua, OpenResty, and PL/Lua were fitted into the project;
- which business flows now depend on these pieces;
- which validations were executed;
- which security concerns must be preserved in future maintenance.

This document was written for subsequent study of the project. Therefore, it prioritizes context, motivation, architecture, and observed behavior.

## 2. Security Rule for This Document

To not compromise the project's security, this file does **not** record:

- real passwords;
- tokens;
- hashes of tokens captured at runtime;
- secret values from `.env`;
- commands with embedded credentials;
- internal addresses that would only make sense on the local development machine.

Whenever a configuration depends on a secret, this document only mentions the environment variable name or the concept involved.

## 3. Implementation Scope

The added integration covers three main fronts:

1. **Redis Lua in the Laravel backend**
2. **OpenResty at the HTTP edge**
3. **PL/Lua in PostgreSQL**

Focused tests and infrastructure adjustments were also added to enable build, bootstrap, and validation in containers.

## 4. Resulting Architecture Overview

After the changes, the flow became:

1. The client continues consuming the Laravel API through `nginx`.
2. `nginx` now runs on OpenResty and executes Lua logic at the edge.
3. The Laravel backend uses Lua scripts in Redis for operations that benefit from atomicity and low latency.
4. PostgreSQL executes PL/Lua to derive `productivity_score` directly at the database layer.
5. The system preserves a **fail-open** strategy at most Lua operational points:
   - if Redis Lua fails, the application tries to proceed with safe behavior;
   - if the edge cannot query Redis, the request is not dropped by mistake;
   - if the PL/Lua trigger fails internally, it falls back to a safe value instead of breaking the entire write.

This approach reduces the risk of accidental unavailability during integrations with dynamic components.

## 5. Implementation Inventory

### 5.1 Redis Lua Scripts

Three scripts were added in `redis-scripts/`:

- `job_dedup.lua`
- `sliding_window.lua`
- `streak_update.lua`

#### `job_dedup.lua`

Responsibility:

- prevent duplicate job dispatch within a short window.

Main usage:

- deduplicate `RecalculateMetricsJob` dispatches after session changes.

Strategy:

- uses `SET key value NX EX ttl` to create a short lock;
- returns `1` when the lock is created;
- returns `0` when the lock already exists.

Benefit:

- prevents bursts of redundant jobs for the same user when multiple changes happen in sequence.

#### `sliding_window.lua`

Responsibility:

- apply sliding window rate limiting with `retry_after` response.

Main usage:

- protect critical study session write endpoints.

Strategy:

- removes old events from a `sorted set`;
- counts events still within the window;
- blocks when the limit is reached;
- calculates how much time remains before the window accepts requests again.

Benefit:

- avoids the coarse behavior of fixed windows and improves throttling precision.

#### `streak_update.lua`

Responsibility:

- update daily study streak with a single Redis round-trip.

Main usage:

- serve as the basis for streak maintenance without multiple round-trips.

Strategy:

- compares `today` and `yesterday` with the last recorded date;
- maintains the streak if it's the same day;
- increments if there was study the next day;
- resets to `1` if there was a break.

Benefit:

- reduces coupling of the streak rule to multiple separate Redis operations.

## 6. Redis Lua Integration in Laravel

### 6.1 `App\Services\RedisLuaService`

File:

- `backend/app/Services/RedisLuaService.php`

Responsibilities:

- map available Lua scripts;
- load scripts into Redis;
- cache SHA values;
- execute via `EVALSHA`;
- automatically reload on `NOSCRIPT`.

Relevant decisions:

- initial loading happens by script name;
- SHA is cached to avoid `SCRIPT LOAD` on every call;
- if Redis loses the script from memory, the service reloads automatically;
- `NOSCRIPT` detection is handled explicitly.

This transformed Lua usage into a reusable abstraction, instead of spreading raw Redis calls throughout the code.

### 6.2 `App\Providers\RedisScriptServiceProvider`

File:

- `backend/app/Providers/RedisScriptServiceProvider.php`

Responsibility:

- load scripts at application boot.

Important characteristic:

- if loading fails, the system logs and proceeds in **fail-open**.

### 6.3 `App\Http\Middleware\SlidingWindowRateLimit`

File:

- `backend/app/Http/Middleware/SlidingWindowRateLimit.php`

Responsibility:

- apply rate limiting via Lua script on write routes.

Operation:

- builds a key per user or IP and per path;
- uses millisecond timestamp;
- queries `sliding_window.lua`;
- returns `429` with `Retry-After` when blocked;
- includes `X-RateLimit-Limit` in response.

Resilience behavior:

- if Redis Lua fails, logs a warning; then, if `services.rate_limit.fail_open` is `true`, the request proceeds; otherwise responds `503` with JSON unavailability payload (see `config/services.php`).

### 6.4 `App\Services\StreakService`

File:

- `backend/app/Services/StreakService.php`

Responsibility:

- encapsulate the use of `streak_update.lua`.

Details:

- fetches user timezone;
- calculates `today` and `yesterday` in the correct timezone;
- delegates to Redis Lua;
- on failure, returns `0` with warning log.

### 6.5 Metrics Recalculation Deduplication

Main file:

- `backend/app/Listeners/StudySession/DispatchMetricsRecalculation.php`

Change:

- the listener now consults `job_dedup.lua` before dispatching `RecalculateMetricsJob`.

Practical effect:

- multiple nearby changes to the same session don't generate an explosion of identical jobs for the same user.

Important:

- if Lua deduplication is unavailable, the listener doesn't abort the flow; it just logs and proceeds.

## 7. Authentication and Token Revocation Changes

### 7.1 `TokenService`

File:

- `backend/app/Modules/Auth/Services/TokenService.php`

Responsibilities:

- centralize Sanctum token revocation;
- send the persisted token hash to Redis blacklist;
- delete the token from the database;
- revoke one or multiple tokens.

Important point:

- Redis stores the **persisted token hash**, not the Bearer plaintext.
- this is consistent with how Sanctum stores tokens.

### 7.2 `AuthController` and `AuthService`

Files:

- `backend/app/Http/Controllers/V1/AuthController.php`
- `backend/app/Modules/Auth/Services/AuthService.php`

Changes:

- `logout` now uses `TokenService`;
- `revokeAllTokens` now uses `TokenService`;
- `login` revokes previous tokens via `TokenService`;
- `changePassword` revokes previous tokens via `TokenService`.

Result:

- revocation is now consistent between backend and edge;
- the blacklist rule no longer depends on isolated `tokens()->delete()` calls.

## 8. Routes and Session Model Changes

### 8.1 Routes

File:

- `backend/routes/api.php`

Changes:

- `throttle.sliding` was applied to `study-sessions` mutation routes (values per `routes/api.php`):
  - `POST /api/v1/study-sessions/start` — sliding limit 10
  - `POST /api/v1/study-sessions` — sliding limit 30
  - `PATCH /api/v1/study-sessions/{id}/end` — sliding limit 10
  - `PUT /api/v1/study-sessions/{id}` and `PATCH /api/v1/study-sessions/{id}` — sliding limit 30
  - `DELETE /api/v1/study-sessions/{id}` — sliding limit 30

This makes the most sensitive rate limiting use the Lua implementation, while the rest of the API continues using existing Laravel throttles.

### 8.2 Middleware Bootstrap

File:

- `backend/bootstrap/app.php`

Change:

- the `throttle.sliding` alias was registered.

### 8.3 Provider Registration

File:

- `backend/bootstrap/providers.php`

Change:

- `RedisScriptServiceProvider` was registered.

### 8.4 `StudySession` and `StudySessionResource`

Files:

- `backend/app/Models/StudySession.php`
- `backend/app/Http/Resources/StudySessionResource.php`

Changes:

- `productivity_score` became part of the model's cast;
- `productivity_score` is now serialized in the API response.

Result:

- the database-calculated field is now explicitly consumable by the API and frontend.

## 9. OpenResty Edge Integration

### 9.1 Proxy Base Swap

Files:

- `docker/nginx/Dockerfile`
- `docker/nginx/nginx.conf`
- `docker/nginx/conf.d/studytrack.conf`

Structural change:

- the proxy stopped being just standard Nginx and started using OpenResty.

Motivation:

- enable Lua logic directly at the HTTP edge.

### 9.2 What the Edge Now Does

#### Simple WAF via Lua

In `rewrite_by_lua_block`, the edge checks:

- suspicious user-agents;
- simple URI patterns related to trivial probes.

Result:

- requests with recognizably offensive agents can be blocked before reaching PHP.

#### Security Headers via Lua

In `header_filter_by_lua_block`, the edge ensures:

- `X-Content-Type-Options`
- `X-Frame-Options`
- `Referrer-Policy`
- `X-Request-ID`
- `Permissions-Policy`
- removal of unnecessary exposure headers

#### Revoked Token Validation at the Edge

In `access_by_lua_block`, the edge:

- preserves `login` and `register` as public routes;
- requires Bearer token on private `api/v1` routes;
- extracts the Sanctum token secret after the `|` separator;
- calculates SHA-256 via `resty.sha256`;
- queries the Redis blacklist using the same database and prefix as Laravel;
- responds `401 {"error":"Token revoked"}` when revocation is found.

### 9.3 Important OpenResty Operational Adjustments

The following adjustments were necessary for the integration to work correctly:

- logs sent to `stdout/stderr` instead of local files;
- `resolver 127.0.0.11` for Docker's internal DNS;
- internal route for revoked token response;
- static `/nginx-health` route for container healthcheck.

### 9.4 Failure Behavior

The edge was designed for **fail-open** at several points:

- if it can't load required Lua libs;
- if it can't connect or authenticate to Redis;
- if it can't query the blacklist;
- if an unexpected error occurs during validation.

This was an operational choice to reduce unavailability from auxiliary dependencies.

## 10. PL/Lua Integration in PostgreSQL

### 10.1 Custom Postgres Build

File:

- `docker/postgres/Dockerfile`

What was done:

- the `postgres` image is now built locally;
- the build installs toolchain, PostgreSQL headers, and Lua 5.4;
- `pllua-ng` is compiled and installed manually.

Relevant technical point:

- the build needed to explicitly fix `LUA`, `LUAC`, `LUA_INCDIR`, and `LUALIB` for Lua 5.4.

### 10.2 Extension Enablement

Files:

- `docker/postgres/init/01-extensions-and-schema.sql`
- `backend/database/migrations/transactional/2026_04_04_000005_add_productivity_score_to_study_sessions_table.php`

Changes:

- `CREATE EXTENSION IF NOT EXISTS pllua` was added in the SQL bootstrap;
- the migration also creates the extension, to not depend only on container init.

This duplication is intentional:

- SQL init covers new databases;
- the migration covers environments where the volume already existed.

### 10.3 `productivity_score` in `study_sessions`

File:

- `backend/database/migrations/transactional/2026_04_04_000005_add_productivity_score_to_study_sessions_table.php`

What was added:

- `productivity_score` column in `study_sessions`;
- `public.calculate_study_session_productivity_score()` function in PL/Lua;
- `trg_study_session_productivity_score` trigger;
- backfill for existing rows.

Applied rule:

- very short sessions receive less weight;
- medium sessions receive default or slightly higher weight;
- long sessions receive a higher multiplier.

Defensive behavior:

- if the Lua logic fails, the trigger falls back to a safe value on the record itself.

### 10.4 Adaptation to the Real Repository

The original proposal anticipated using another analytical structure, but the final implementation was adapted to the actual state of the project:

- the score went into `public.study_sessions`;
- the trigger operates on the transactional table that actually exists in the repository.

## 11. Docker Compose Infrastructure Adjustments

File:

- `docker-compose.yml`

Relevant changes:

- `nginx` now uses a local build on OpenResty;
- `postgres` now uses a local build with PL/Lua;
- `nginx` received necessary Redis variables for blacklist queries;
- PHP services now receive Redis password and port;
- `nginx` now has a healthcheck on `/nginx-health`;
- a new volume was created for the Postgres variant with `pllua`.

### 11.1 Security Note on Compose

The current compose remains oriented to **local development**.

This means:

- default values and environment fallbacks should not be reused in production;
- documentation should always reference variables, not values;
- any production hardening needs to move secrets to an appropriate secret management mechanism.

## 12. Added Tests and Executed Validation

### 12.1 Added Tests

Folder:

- `backend/tests/Feature/LuaScripts/`

Files:

- `JobDedupTest.php`
- `SlidingWindowTest.php`
- `StreakTest.php`

### 12.2 What Each Test Covers

#### `JobDedupTest`

Validates:

- first lock allowed;
- second immediate call blocked;
- behavior after TTL, also respecting job uniqueness.

#### `SlidingWindowTest`

Validates:

- request within limit;
- request blocked with `429`;
- request allowed again after window reset.

#### `StreakTest`

Validates:

- first session;
- consecutive day;
- streak break;
- same-day repetition.

### 12.3 Observed Result

The Lua-focused test suite was executed in a container and passed:

- `10` tests approved;
- `27` assertions approved.

### 12.4 Executed Operational Validations

Validated at runtime:

- Postgres build with `pllua`;
- `pllua` extension active in the database;
- `productivity_score` migration executed;
- `nginx` healthy;
- public auth routes preserved;
- `401` block on private route without token;
- `403` WAF block by suspicious user-agent;
- token revocation reflected at the edge with `{"error":"Token revoked"}`.

## 13. Technical Review and Residual Risks

During the review, the following points required attention or correction:

### 13.1 Points Corrected During Validation

- PL/Lua trigger initially used incorrect trigger API and was adjusted to `pllua`'s actual syntax;
- `pllua-ng` build needed explicit configuration for Lua 5.4;
- OpenResty needed Docker `resolver` to query `redis`;
- revoked token validation needed alignment:
  - Redis database;
  - Laravel Redis prefix;
  - Sanctum token secret hash;
  - correct SHA-256 library in OpenResty;
- `nginx` healthcheck was moved to a static route on the proxy itself.

### 13.2 Risks That Still Warrant Future Observation

- the **fail-open** strategy is operationally resilient but deliberately permissive on auxiliary failures;
- the custom `postgres` uses `user: "0:0"` in local compose for volume compatibility, which should not be treated as ideal production design;
- token blacklist depends on Redis availability and consistency between Laravel and edge prefix/database;
- WAF logic is intentionally simple and does not replace specialized protection.

## 14. New or Changed Files by Area

### Redis Lua

- `redis-scripts/job_dedup.lua`
- `redis-scripts/sliding_window.lua`
- `redis-scripts/streak_update.lua`
- `backend/app/Services/RedisLuaService.php`
- `backend/app/Providers/RedisScriptServiceProvider.php`
- `backend/app/Services/StreakService.php`
- `backend/app/Http/Middleware/SlidingWindowRateLimit.php`

### Authentication and Tokens

- `backend/app/Modules/Auth/Services/TokenService.php`
- `backend/app/Modules/Auth/Services/AuthService.php`
- `backend/app/Http/Controllers/V1/AuthController.php`

### Study Session and Analytics

- `backend/app/Listeners/StudySession/DispatchMetricsRecalculation.php`
- `backend/app/Models/StudySession.php`
- `backend/app/Http/Resources/StudySessionResource.php`
- `backend/database/migrations/transactional/2026_04_04_000005_add_productivity_score_to_study_sessions_table.php`

### Bootstrap and Routes

- `backend/bootstrap/app.php`
- `backend/bootstrap/providers.php`
- `backend/routes/api.php`

### Infrastructure

- `docker/nginx/Dockerfile`
- `docker/nginx/nginx.conf`
- `docker/nginx/conf.d/studytrack.conf`
- `docker/postgres/Dockerfile`
- `docker/postgres/init/01-extensions-and-schema.sql`
- `docker-compose.yml`

### Tests

- `backend/tests/Feature/LuaScripts/JobDedupTest.php`
- `backend/tests/Feature/LuaScripts/SlidingWindowTest.php`
- `backend/tests/Feature/LuaScripts/StreakTest.php`

## 15. Recommended Study Guide

To understand this integration with good pedagogical order, the recommended sequence is:

1. Read this entire document.
2. Read the three scripts in `redis-scripts/`.
3. Read `RedisLuaService` and `RedisScriptServiceProvider`.
4. Read `SlidingWindowRateLimit` and `DispatchMetricsRecalculation`.
5. Read `TokenService`, `AuthService`, and `AuthController`.
6. Read the `productivity_score` migration.
7. Read `docker/nginx/conf.d/studytrack.conf`.
8. Read the tests in `backend/tests/Feature/LuaScripts/`.

This order helps understand atomic behavior first, then application integration, then infrastructure, and finally automated proof.

## 16. Conclusion

The Lua integration added three new capabilities to the project:

- **atomicity and efficiency in Redis** for deduplication, throttling, and streak;
- **edge control in OpenResty** for security, headers, and token blacklist;
- **derived logic in PostgreSQL with PL/Lua** for `productivity_score`.

The final result was not just a set of isolated scripts. The implementation became part of the application's real flow, with:

- bootstrap;
- middlewares;
- listeners;
- authentication;
- Docker infrastructure;
- automated tests;
- runtime validation.

As a study reference, this document should be kept synchronized whenever there are changes to:

- Lua scripts;
- token blacklist contract;
- rate limit strategy;
- `productivity_score` trigger;
- `nginx` or `postgres` image;
- environment variables relevant to Redis and edge.
