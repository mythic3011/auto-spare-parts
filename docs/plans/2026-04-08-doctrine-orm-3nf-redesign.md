# Canonical Implementation Plan: Doctrine ORM, Shared PHP API, and SvelteKit Migration

Status: canonical merged plan.

Merged from:

- `docs/plans/2026-04-08-doctrine-orm-3nf-redesign.md`
- `docs/plans/2026-05-26-backend-shared-php-api-refactor.md`

This file is the source of truth for the modernization work. Do not create a second backend migration plan unless this file first marks that split as intentional.

## AGENTS Skill Routing

Use these AGENTS skills before implementation:

- `core/architecture`: system boundaries, API shape, repositories, schema, service layering
- `core/lang`: PHP, TypeScript, and shell implementation standards
- `core/security`: auth, authorization, validation, secrets, transport, logs
- `core/testing`: focused test strategy and fixtures
- `governance/docs`: docs must match actual behavior
- `workflow/change-slicing`: small reviewable implementation slices
- `workflow/writing-plans`: ordered steps, verification commands, stop conditions, commit boundaries

Optional skills:

- `core/docker`: only when changing Dockerfiles, Compose, Nginx, or runtime topology
- `governance/ci`: only when changing CI or repo checks
- `core/git`: only when staging, committing, branching, or reviewing repo history

## Agent Operating Rules

1. Start every implementation session by reading this file, then run `rtk git status --short`.
2. Do not revert unrelated dirty work. If a file is already modified, inspect it before editing.
3. Do not implement a later slice until the current slice has focused verification.
4. Do not mix UI redesign, schema rewrite, shared API migration, and cleanup in the same commit.
5. Do not create new direct PHP endpoint scripts. New HTTP behavior must go through the shared API route contract.
6. Do not trust user identity from query strings, request bodies, local storage, or frontend stores.
7. Do not expose stack traces, SQL errors, token values, password hashes, or raw PII in responses or logs.
8. Do not use XAMPP/WAMP guidance. Use Docker-compatible workflows.
9. Do not use broad repo-wide formatters inside a narrow behavior slice.
10. If a step is already implemented, verify it and move forward. Do not recreate scaffolding.

## Goal

Modernize the project from scattered raw-SQL PHP scripts and static/PHP-rendered pages into a maintainable monorepo with:

- a shared PHP API contract during the transition
- a versioned `/api/v1` backend target under `apps/api`
- Doctrine ORM and Doctrine Migrations as the long-term schema authority
- normalized 3NF relational data
- SvelteKit as the frontend application
- centralized request parsing, response envelopes, auth, authorization, validation, and route registration

The migration must be incremental. The existing application should stay runnable until each replacement path is verified.

## High-Level Strategy

Use coexistence first, then cut over.

1. Stabilize one shared PHP API contract.
2. Route legacy clients through compatibility aliases.
3. Move backend behavior behind services and repositories one domain at a time.
4. Move persistence from raw SQL classes to Doctrine repositories one aggregate at a time.
5. Switch SvelteKit and legacy JavaScript clients to shared or `/api/v1` paths.
6. Remove direct endpoint scripts and PHP-rendered business pages only after tests prove parity.

The implemented routing and checkout behavior is documented in
[the transitional runtime contract](../api/runtime-contract.md). The route map below
remains the migration target; only v1 authentication is currently implemented.

## Current Repo Reality

The repository already contains a mixed transitional state:

- legacy endpoint scripts under `resources/php/*.php`
- legacy domain classes under `resources/php/class/*.php`
- shared API support under `resources/php/class/Api/**`
- a compatibility gateway at `resources/php/api/index.php`
- newer Doctrine API code under `apps/api/**`
- newer SvelteKit code under `apps/web/**`
- frontend API assumptions under `apps/web/src/lib/api/**`
- endpoint path checks under `scripts/checks/check-hardcoded-api-paths.sh`

Do not assume the old plan starts from zero. Inspect files before adding scaffolding.

## Ownership Boundaries

### Transitional API Layer

Owner during migration:

- `resources/php/class/Api/**`
- `resources/php/api/index.php`
- legacy endpoint shims under `resources/php/*.php`

Allowed responsibilities:

- parse legacy action names
- preserve compatibility aliases
- call shared validators/auth guards
- call existing legacy domain classes while their Doctrine replacements are not ready
- emit the canonical response envelope

Not allowed:

- add new business logic directly into endpoint scripts
- add new direct public scripts
- trust frontend-provided user ids
- return endpoint-specific envelope shapes

### Long-Term Backend Layer

Owner after migration:

- `apps/api/public/index.php`
- `apps/api/src/Application/**`
- `apps/api/src/Domain/**`
- `apps/api/src/Infrastructure/**`
- `apps/api/src/Presentation/**`
- `apps/api/migrations/**`
- `apps/api/config/**`
- `apps/api/bootstrap/**`

Allowed responsibilities:

- versioned `/api/v1` routes
- application services
- Doctrine entities and repositories
- domain invariants
- database migrations
- backend response contract

Not allowed:

- depend on SvelteKit internals
- depend on legacy page paths
- expose internal numeric database ids in public URLs or API payloads

### Frontend Layer

Owner:

- `apps/web/**`
- legacy JavaScript only until retired: `resources/js/**`

Allowed responsibilities:

- call shared API or `/api/v1` endpoints
- render role-aware UI
- hold non-authoritative UI state
- handle client-side form affordances

Not allowed:

- direct database access
- frontend-only authorization
- hardcoded direct script URLs after endpoint switch
- new business logic copied from PHP classes

## Target Runtime Topology

```text
Browser
  -> SvelteKit app in apps/web
  -> /api/v1/* HTTP calls
  -> PHP API in apps/api
  -> Application service
  -> Domain model
  -> Repository interface
  -> Doctrine repository
  -> MySQL or PostgreSQL
```

During transition:

```text
Browser or legacy page
  -> shared API gateway or legacy alias
  -> resources/php/class/Api shared boundary
  -> existing legacy class or new apps/api service
  -> legacy tables or Doctrine-managed tables
```

## Target Monorepo Tree

```text
auto-spare-parts/
  apps/
    api/
      public/
      src/
        Application/
        Domain/
        Infrastructure/
        Presentation/
      config/
      bootstrap/
      migrations/
      tests/
    web/
      src/
      static/
      tests/
      package.json
      svelte.config.js
      vite.config.ts
  packages/
    shared-types/
  docker/
    nginx/
    php/
    web/
    dragonfly/
  docs/
    api/
    architecture/
    plans/
    schema/
  scripts/
    checks/
  storage/
    media/
  resources/
    php/
      class/
      api/
  tests/
```

## Canonical API Contract

All migrated API responses must use one envelope shape.

Success:

```json
{
  "success": true,
  "data": {},
  "error": null,
  "meta": {
    "requestId": "request-id"
  }
}
```

Error:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "validation_failed",
    "message": "Validation failed",
    "details": {}
  },
  "meta": {
    "requestId": "request-id"
  }
}
```

Rules:

- `success` is a boolean, never a string.
- `data` is always present.
- `error` is `null` on success.
- `error.code` is stable and machine-readable.
- `error.message` is safe for display.
- `error.details` is optional but must not contain secrets, stack traces, SQL errors, token values, password hashes, or raw PII.
- `meta.requestId` comes from `X-Request-Id` when valid, otherwise from generated server-side entropy.
- Compatibility adapters may temporarily accept `status/message/data` and nested `result`, but new responses must use the canonical envelope.

## HTTP Rules

Use these conventions for all migrated routes:

- `GET`: read, idempotent, no request body required
- `POST`: create or command-style action
- `PUT`: full replacement only
- `PATCH`: partial update or state transition
- `DELETE`: removal or cancellation only when the domain supports it

Public route format:

```text
/api/v1/resources
/api/v1/resources/{publicId}
/api/v1/resources/{publicId}/sub-resources
```

Legacy aliases may remain temporarily:

```text
dealer-list
dealer-get
manager-list
manager-get
getSparePartList
getSparePart
profile.get
cart.add
```

Aliases must map to the same service logic as canonical routes. Do not fork behavior.

## Target Route Map

| Domain | Canonical route | Legacy alias or script | Auth | Owner during migration | Final owner |
| --- | --- | --- | --- | --- | --- |
| Login | `POST /api/v1/auth/login` | `user_api.php?action=login` | public plus CSRF where applicable | shared API gateway | `apps/api` |
| Logout | `POST /api/v1/auth/logout` | `user_api.php?action=logout` | user | shared API gateway | `apps/api` |
| Current user | `GET /api/v1/auth/me` | `verify_token` compatibility | user | shared API gateway | `apps/api` |
| Dealer spare parts | `GET /api/v1/dealer/spare-parts` | `getSparePartList` | dealer or staff | shared API gateway | `apps/api` |
| Spare part detail | `GET /api/v1/dealer/spare-parts/{partPublicId}` | `getSparePart` | dealer or staff | shared API gateway | `apps/api` |
| Dealer orders | `GET /api/v1/dealer/orders` | `dealer-list` | dealer | shared API gateway | `apps/api` |
| Dealer order detail | `GET /api/v1/dealer/orders/{orderPublicId}` | `dealer-get` | dealer scoped | shared API gateway | `apps/api` |
| Place order | `POST /api/v1/dealer/orders` | `place-order` | dealer | shared API gateway | `apps/api` |
| Cart read | `GET /api/v1/dealer/cart` | `CartApi.php?action=get` | dealer | shared API gateway | `apps/api` |
| Cart add | `POST /api/v1/dealer/cart/items` | `CartApi.php?action=add` | dealer | shared API gateway | `apps/api` |
| Cart update | `PATCH /api/v1/dealer/cart/items/{partPublicId}` | `update` | dealer | shared API gateway | `apps/api` |
| Profile read | `GET /api/v1/profile` | `profile_api.php?action=get` | user | shared API gateway | `apps/api` |
| Profile update | `PATCH /api/v1/profile` | `profile_api.php?action=edit` | user | shared API gateway | `apps/api` |
| Staff orders | `GET /api/v1/staff/orders` | `manager-list` | sales manager | shared API gateway | `apps/api` |
| Staff order detail | `GET /api/v1/staff/orders/{orderPublicId}` | `manager-get` | sales manager | shared API gateway | `apps/api` |
| Staff order status | `PATCH /api/v1/staff/orders/{orderPublicId}/status` | `update-status` | sales manager | shared API gateway | `apps/api` |
| Staff items | `GET /api/v1/staff/items` | legacy item actions | sales manager | shared API gateway | `apps/api` |
| Staff item create | `POST /api/v1/staff/items` | legacy item actions | sales manager | shared API gateway | `apps/api` |
| Staff item update | `PATCH /api/v1/staff/items/{partPublicId}` | legacy item actions | sales manager | shared API gateway | `apps/api` |
| Stock report | `GET /api/v1/staff/reports/stock` | `stock-report` | sales manager | shared API gateway | `apps/api` |

## Authentication and Authorization Contract

Authentication:

- Login validates email and password at the API boundary.
- Passwords use `password_hash` and `password_verify`.
- Token storage must store hashes, not raw token values, in the long-term target.
- Logout revokes the token or session server-side.
- Missing, invalid, expired, or revoked tokens return `401`.

Authorization:

- Every protected route checks authorization server-side.
- Dealer routes are scoped to the authenticated dealer.
- Sales manager routes require a sales-manager role or matching permission.
- Frontend role checks are UI hints only.
- Route metadata must declare auth requirements.

Identity source rules:

- Valid: authenticated session, validated bearer token, server-side auth context.
- Invalid: `userId` query string, `dealerID` request body, local storage, hidden form fields.

## Security Requirements

- Validate every request at the API boundary.
- Reject malformed JSON with `400 invalid_json`.
- Reject unknown fields on write DTOs unless explicitly accepted for compatibility.
- Use allowlists for status transitions, roles, permissions, MIME types, and sortable fields.
- Use prepared statements or Doctrine query builders only.
- Do not concatenate SQL.
- Do not log passwords, tokens, raw PII, or full request bodies.
- Do not expose stack traces or raw exception messages in production responses.
- Set JSON `Content-Type`, `X-Content-Type-Options: nosniff`, no-store cache headers, and `X-Request-Id`.
- Use Docker/runtime env vars for configuration.
- Do not hardcode secrets, database credentials, ports, or URLs.

## Target Data Model

Internal relational integrity uses numeric `id` keys. Public URLs and public API payloads use `public_id` values.

Public id examples:

- users: `usr_<uuidv7>`
- orders: `ord_<uuidv7>`
- spare parts: `prt_<uuidv7>`
- media assets: `med_<uuidv7>`

Foreign keys always reference internal `id` columns.

### Tables

`permissions`

- `id` bigint primary key
- `code` varchar(64) unique not null
- `name` varchar(128) unique not null

`roles`

- `id` bigint primary key
- `code` varchar(32) unique not null
- `name` varchar(64) unique not null

`role_permissions`

- `id` bigint primary key
- `role_id` bigint not null foreign key to `roles.id`
- `permission_id` bigint not null foreign key to `permissions.id`
- unique `role_id`, `permission_id`

`users`

- `id` bigint primary key
- `public_id` varchar(48) unique not null
- `email` varchar(255) unique not null
- `password_hash` varchar(255) not null
- `role_id` bigint not null foreign key to `roles.id`
- `is_active` boolean not null default true
- `created_at` datetime not null
- `updated_at` datetime not null

`dealer_profiles`

- `user_id` bigint primary key and foreign key to `users.id`
- `company_name` varchar(100) not null
- `contact_name` varchar(100) not null
- `contact_number` varchar(32) not null
- `fax_number` varchar(32) nullable
- `delivery_address_line_1` varchar(120) not null
- `delivery_address_line_2` varchar(120) nullable
- `district` varchar(120) nullable
- `city` varchar(120) not null
- `region` varchar(120) nullable
- `country_code` char(2) not null

`sales_manager_profiles`

- `user_id` bigint primary key and foreign key to `users.id`
- `manager_name` varchar(100) not null
- `contact_name` varchar(100) not null
- `contact_number` varchar(32) not null

`spare_part_categories`

- `id` smallint primary key
- `code` varchar(32) unique not null
- `name` varchar(100) unique not null

`spare_parts`

- `id` bigint primary key
- `public_id` varchar(48) unique not null
- `category_id` smallint not null foreign key to `spare_part_categories.id`
- `sku` varchar(50) unique not null
- `primary_image_asset_id` bigint nullable foreign key to `media_assets.id`
- `name` varchar(255) not null
- `description` text nullable
- `weight_kg` decimal(10,3) not null
- `stock_quantity` int not null
- `unit_price` decimal(12,2) not null

`media_assets`

- `id` bigint primary key
- `public_id` varchar(48) unique not null
- `storage_disk` varchar(32) not null
- `storage_path` varchar(255) not null
- `original_filename` varchar(255) not null
- `mime_type` varchar(100) not null
- `checksum_sha256` char(64) not null
- `width` int nullable
- `height` int nullable
- `created_at` datetime not null
- `updated_at` datetime not null

`order_statuses`

- `id` smallint primary key
- `code` varchar(32) unique not null
- `name` varchar(64) unique not null

`orders`

- `id` bigint primary key
- `public_id` varchar(48) unique not null
- `order_number` varchar(32) unique not null
- `dealer_user_id` bigint not null foreign key to `users.id`
- `sales_manager_user_id` bigint not null foreign key to `users.id`
- `status_id` smallint not null foreign key to `order_statuses.id`
- delivery address snapshot fields matching dealer address shape
- `delivery_date` date not null
- `shipping_cost` decimal(12,2) not null
- `created_at` datetime not null
- `updated_at` datetime not null

`order_items`

- `id` bigint primary key
- `order_id` bigint not null foreign key to `orders.id` with cascade delete
- `spare_part_id` bigint not null foreign key to `spare_parts.id`
- `quantity` int not null
- `unit_price` decimal(12,2) not null
- unique `order_id`, `spare_part_id`

`auth_tokens`

- `id` bigint primary key
- `user_id` bigint not null foreign key to `users.id` with cascade delete
- `token_hash` char(64) unique not null
- `expires_at` datetime not null
- `created_at` datetime not null
- index `user_id`, `expires_at`

`login_attempts`

- `id` bigint primary key
- `email` varchar(255) not null
- `ip_address` varchar(45) not null
- `attempted_at` datetime not null
- `was_successful` boolean not null
- index `email`, `attempted_at`
- index `ip_address`, `attempted_at`

### 3NF Rules

- Authentication data lives only in `users`.
- Role-specific attributes live in profile tables.
- Authorization is permission-based through `roles`, `permissions`, and `role_permissions`.
- Category and order status labels are lookup tables.
- `order_items.unit_price` is a historical transaction snapshot.
- Delivery address on `orders` is a historical shipping snapshot.
- Doctrine migrations, not hand-written vendor-specific DDL, are the long-term schema authority.

## Cache Target

Introduce a backend cache abstraction only after the database-backed behavior is correct.

Cacheable:

- permission resolution by user public id
- spare-part catalog reads
- order read models
- login/rate-limit counters
- short-lived token validation if invalidation is explicit

Not cacheable as authority:

- stock mutations
- order status transitions
- user/profile writes
- token revocation state without invalidation

Default deployment target: DragonflyDB through Redis-compatible protocol.

## Implementation Slices

Each slice below has a single review boundary. If an agent cannot finish the slice, it must stop with findings and not continue into the next slice.

### Slice 0: Preflight and Baseline

Goal:

- Establish current state without changing behavior.

Files to read:

- `composer.json`
- `phpunit.xml`
- `tests/bootstrap.php`
- `apps/api/tests/bootstrap.php`
- `apps/web/package.json`
- `resources/php/class/Api/**`
- `resources/php/api/index.php`
- `apps/api/public/index.php`
- `apps/web/src/lib/api/**`

Steps:

1. Run `rtk git status --short`.
2. Confirm whether `composer.lock` exists before promising dependency lock updates.
3. Confirm whether `vendor/` and `apps/web/node_modules/` exist before running dependency-heavy checks.
4. Read current endpoint config before editing paths.
5. Record any baseline failures in the implementation note or PR body.

Verification:

```bash
rtk rg --files
rtk git status --short
rtk composer --version
rtk npm --prefix apps/web --version
```

Stop conditions:

- `composer` is unavailable and the slice requires dependency installation.
- the target file has unrelated dirty changes that conflict with the planned edit.
- baseline tests fail in a way unrelated to the planned slice and cannot be isolated.

Commit boundary:

- No commit unless only docs or baseline scripts were changed.

### Slice 1: Shared API Response Contract

Goal:

- Make one canonical response envelope available to both transitional and final API code.

Files:

- `resources/php/class/Api/*`
- `resources/php/class/ResponseHandler.php`
- `apps/api/src/Presentation/Http/*`
- `tests/Unit/ResponseHandlerTest.php`
- new focused tests if needed

Steps:

1. Add or refactor a shared response object/helper that emits the canonical envelope.
2. Keep old `ResponseHandler` behavior only as a compatibility adapter if existing tests or pages require it.
3. Add tests for success, validation error, unauthorized, forbidden, not found, method not allowed, and internal error.
4. Add request-id propagation tests.
5. Ensure production errors are generic.

Verification:

```bash
rtk vendor/bin/phpunit tests/Unit/ResponseHandlerTest.php
rtk vendor/bin/phpunit tests/Unit/ApiBootstrapTest.php
```

Stop conditions:

- changing the envelope breaks current frontend tests without a compatibility adapter.
- an error path exposes raw exception text or SQL details.

Commit boundary:

- shared response code and its tests only.

### Slice 2: Shared Route Registry and Gateway

Goal:

- Route all transitional endpoint access through one registry without forking behavior.

Files:

- `resources/php/class/Api/RouteRegistry.php`
- `resources/php/class/Api/ApiRequest.php`
- `resources/php/class/Api/ApiAuthContext.php`
- `resources/php/api/index.php`
- `tests/Unit/RouteRegistryTest.php`
- `tests/Unit/ApiRequestTest.php`
- `tests/Unit/ApiAuthContextTest.php`

Steps:

1. Centralize route definitions in one factory or registry builder.
2. Define method, auth requirement, role or permission requirement, canonical route key, and legacy aliases for each route.
3. Normalize route keys from both path segments and legacy `action` values.
4. Reject unknown routes with `404`.
5. Reject wrong methods with `405`.
6. Keep endpoint scripts as shims only if existing pages still call them.

Verification:

```bash
rtk vendor/bin/phpunit tests/Unit/RouteRegistryTest.php tests/Unit/ApiRequestTest.php tests/Unit/ApiAuthContextTest.php
```

Stop conditions:

- a route requires reading user identity from a request parameter.
- two aliases call different implementations for the same behavior.

Commit boundary:

- route registry, request parsing, auth metadata, and focused unit tests only.

### Slice 3: Doctrine Foundation and Schema Authority

Goal:

- Confirm Doctrine is the long-term persistence layer and keep migrations portable.

Files:

- `composer.json`
- `apps/api/bootstrap/doctrine.php`
- `apps/api/config/doctrine.php`
- `apps/api/src/Infrastructure/Doctrine/**`
- `apps/api/migrations/**`
- `apps/api/tests/**`

Steps:

1. Verify Doctrine dependencies in `composer.json`.
2. If dependency updates are needed, run Composer only when available and update lockfiles in the same slice.
3. Ensure Doctrine bootstrap is env-driven.
4. Keep legacy `Database` untouched until each domain is migrated.
5. Add or update a test that constructs the entity manager from test config.
6. Add migration tests or dry-run checks where feasible.

Verification:

```bash
rtk vendor/bin/phpunit apps/api/tests
rtk vendor/bin/phpunit apps/api/tests/Unit
```

Stop conditions:

- Composer is unavailable and lockfile changes are required.
- migration SQL is vendor-specific without an explicit fallback.
- bootstrap requires production credentials.

Commit boundary:

- dependency declarations, Doctrine config, migrations, and Doctrine tests only.

### Slice 4: Lookup, RBAC, User, and Profile Entities

Goal:

- Implement the identity and authorization schema before migrating auth behavior.

Files:

- `apps/api/src/Domain/Identity/**`
- `apps/api/src/Domain/Common/**`
- `apps/api/src/Infrastructure/Doctrine/Repository/**`
- `apps/api/migrations/**`
- `apps/api/tests/Unit/Entity/**`

Steps:

1. Define entities for permissions, roles, role permissions, users, dealer profiles, and sales manager profiles.
2. Add value objects for public ids and addresses where they reduce duplicated validation.
3. Enforce non-empty role codes, email normalization, active-user checks, and profile ownership invariants.
4. Add repository interfaces before Doctrine implementations.
5. Add tests for entity invariants and repository contract behavior.

Verification:

```bash
rtk vendor/bin/phpunit apps/api/tests/Unit/Entity
rtk vendor/bin/phpunit apps/api/tests/Unit/Auth
```

Stop conditions:

- public API payloads expose internal numeric ids.
- role checks are duplicated in frontend-only code.
- entity constructors allow invalid required fields.

Commit boundary:

- identity/RBAC entities, repositories, migrations, and tests only.

### Slice 5: Auth API Migration

Goal:

- Make auth routes use shared API behavior and Doctrine-backed auth where available.

Files:

- `apps/api/public/index.php`
- `apps/api/src/Application/Auth/AuthService.php`
- `apps/api/src/Domain/Identity/Repository/**`
- `apps/api/src/Infrastructure/Doctrine/Repository/**`
- `resources/php/user_api.php`
- `resources/php/login.php`
- `resources/php/class/Api/**`
- `tests/Api/AuthApiTest.php`
- `apps/api/tests/Unit/Auth/**`

Steps:

1. Define one login request DTO: email and password.
2. Validate missing, malformed, and oversized fields before service execution.
3. Normalize email before lookup.
4. Issue tokens through one repository path.
5. Store token hashes, not raw token values, for new token storage.
6. Return canonical envelope for login, logout, and current-user routes.
7. Keep legacy login aliases until all clients use canonical paths.
8. Add compatibility tests for current legacy login behavior if pages still rely on it.

Verification:

```bash
rtk vendor/bin/phpunit tests/Api/AuthApiTest.php
rtk vendor/bin/phpunit apps/api/tests/Unit/Auth
rtk npm --prefix apps/web run test -- login-response.test.ts
```

Stop conditions:

- login response includes password hashes or raw token hashes.
- logout succeeds without revoking server-side token/session state.
- frontend token storage changes without a tested replacement.

Commit boundary:

- auth service, auth routes, auth tests, and minimal compatibility shims only.

### Slice 6: Spare Part Catalog and Media Reads

Goal:

- Move read-only catalog behavior behind shared routes before write behavior.

Files:

- `resources/php/api.php`
- `resources/php/ViewSparePart.php`
- `resources/php/SparePartDetail.php`
- `resources/php/class/SparePart.php`
- `apps/api/src/Domain/Catalog/**`
- `apps/api/src/Domain/Media/**`
- `apps/api/src/Application/Catalog/**`
- `apps/api/src/Infrastructure/Doctrine/Repository/**`
- `tests/Api/SparePartApiTest.php`
- `apps/web/src/lib/api/types.ts`

Steps:

1. Add canonical read DTOs for catalog list and detail.
2. Map legacy item fields to frontend DTO fields in one adapter.
3. Keep public API ids as `partPublicId` or equivalent public id values.
4. Validate missing/invalid ids.
5. Add pagination or explicit limits before exposing broad list endpoints.
6. Do not migrate item writes in this slice.

Verification:

```bash
rtk vendor/bin/phpunit tests/Api/SparePartApiTest.php
rtk npm --prefix apps/web run test -- api-envelope.test.ts
```

Stop conditions:

- a catalog response exposes internal numeric ids as route ids.
- list endpoints are unbounded without an intentional limit.
- media paths allow traversal or unvalidated file access.

Commit boundary:

- read-only catalog routes, adapters, tests, and DTO alignment only.

### Slice 7: Profile API Migration

Goal:

- Move profile read/update/password behavior behind authenticated shared routes.

Files:

- `resources/php/profile_api.php`
- `resources/php/class/User.php`
- `apps/api/src/Application/Profile/**`
- `apps/api/src/Domain/Identity/**`
- `tests/Api/*Profile*`
- `apps/web/src/lib/api/types.ts`

Steps:

1. Read profile for the authenticated user only.
2. Reject body-provided user ids.
3. Validate profile fields with allowlisted keys.
4. Validate current password, new password, and confirm password server-side.
5. Return canonical validation errors.
6. Add tests for missing auth, wrong current password, mismatched password confirmation, and successful update.

Verification:

```bash
rtk vendor/bin/phpunit tests/Api
rtk vendor/bin/phpunit tests/Unit/UserTest.php
```

Stop conditions:

- update logic can modify another user's profile.
- password update logs raw password values.
- compatibility mapping silently drops validation errors.

Commit boundary:

- profile routes, profile service/adapter, and tests only.

### Slice 8: Cart API Migration

Goal:

- Centralize cart commands and stock validation behind shared API routes.

Files:

- `resources/php/CartApi.php`
- `resources/php/class/Cart.php`
- `resources/php/class/SparePart.php`
- `apps/api/src/Application/Cart/**`
- `tests/Unit/CartTest.php`
- `tests/Integration/CartCheckoutTest.php`

Steps:

1. Normalize cart item identity to public id in API payloads.
2. Validate quantities as positive integers for add/update.
3. Treat quantity zero as removal only if the route explicitly supports it.
4. Check stock before mutating cart state.
5. Return conflict errors for out-of-stock or over-stock requests.
6. Keep cart state deterministic in tests.

Verification:

```bash
rtk vendor/bin/phpunit tests/Unit/CartTest.php
rtk vendor/bin/phpunit tests/Integration/CartCheckoutTest.php
```

Stop conditions:

- cart commands rely on a body-provided user id as authority.
- stock checks happen after mutation.
- tests depend on hidden shared cart state.

Commit boundary:

- cart API behavior, cart tests, and minimal adapters only.

### Slice 9: Order Aggregate and Checkout Migration

Goal:

- Move order placement and dealer order reads behind scoped services.

Files:

- `resources/php/order_api.php`
- `resources/php/class/Order.php`
- `resources/php/class/Shipping.php`
- `apps/api/src/Domain/Ordering/**`
- `apps/api/src/Application/Ordering/**`
- `apps/api/src/Infrastructure/Doctrine/Repository/**`
- `tests/Api/OrderApiTest.php`
- `tests/Integration/OrderFlowTest.php`

Steps:

1. Add order aggregate invariants for dealer, status, delivery date, shipping address, items, quantity, and unit price snapshot.
2. Validate checkout payload before creating the order.
3. Use a transaction for order creation and stock mutation.
4. Scope dealer order reads to the authenticated dealer.
5. Use public order ids in route payloads.
6. Keep legacy `dealer-list`, `dealer-get`, and `place-order` aliases until clients switch.

Verification:

```bash
rtk vendor/bin/phpunit tests/Api/OrderApiTest.php
rtk vendor/bin/phpunit tests/Integration/OrderFlowTest.php
rtk vendor/bin/phpunit apps/api/tests/Unit/Entity/OrderAggregateTest.php
```

Stop conditions:

- checkout can partially create an order without stock consistency.
- dealer can read another dealer's order.
- order response leaks internal numeric ids as public identifiers.

Commit boundary:

- dealer order read/write behavior and tests only.

### Slice 10: Staff Order, Item, and Reporting Migration

Goal:

- Move staff-only actions behind role or permission checks.

Files:

- `resources/php/order_api.php`
- `resources/php/ManageItem.php`
- `resources/php/genReport.php`
- `resources/php/class/Report.php`
- `apps/api/src/Application/Staff/**`
- `tests/Api/OrderApiTest.php`
- relevant staff tests

Steps:

1. Add sales-manager permission guard for every staff route.
2. Validate staff item writes through request DTOs.
3. Validate order status transitions through an allowlisted state machine.
4. Make reporting queries use scoped, parameterized, deterministic inputs.
5. Add tests for forbidden dealer access, invalid filters, invalid status transition, and happy-path manager access.

Verification:

```bash
rtk vendor/bin/phpunit tests/Api/OrderApiTest.php
rtk vendor/bin/phpunit tests/Integration/OrderFlowTest.php
```

Stop conditions:

- reporting accepts raw SQL fragments, unchecked sort fields, or unbounded date ranges.
- staff route authorization exists only in frontend navigation.
- status transitions are free-text updates.

Commit boundary:

- staff-only API behavior, reporting queries, status transition tests only.

### Slice 11: SvelteKit API Client and Route Integration

Goal:

- Make frontend calls match the canonical backend contract.

Files:

- `apps/web/src/lib/api/client.ts`
- `apps/web/src/lib/api/types.ts`
- `apps/web/src/lib/config/endpoints.ts`
- `apps/web/src/lib/stores/**`
- `apps/web/src/routes/**`
- `apps/web/tests/**`
- legacy endpoint config under `resources/js/module/config/**`

Steps:

1. Update the API client to consume the canonical envelope.
2. Keep compatibility parsing only while backend responses still require it.
3. Move route paths to `/api/v1` or the shared gateway.
4. Remove direct references to legacy endpoint scripts from frontend and legacy JS config.
5. Add or update tests for login, envelope handling, auth routing, permission gates, and endpoint config.
6. Run the hardcoded path check.

Verification:

```bash
rtk npm --prefix apps/web run test
rtk npm --prefix apps/web run build
rtk bash scripts/checks/check-hardcoded-api-paths.sh
```

Stop conditions:

- frontend route guards become the only authorization control.
- endpoint config introduces direct `resources/php/*.php` calls.
- TypeScript uses `any` instead of `unknown` plus guards.

Commit boundary:

- API client, endpoint config, route integration tests, and minimal page wiring only.

### Slice 12: Legacy Endpoint Retirement

Goal:

- Remove or reduce direct scripts after clients and tests no longer depend on them.

Files:

- `resources/php/api.php`
- `resources/php/order_api.php`
- `resources/php/profile_api.php`
- `resources/php/CartApi.php`
- `resources/php/user_api.php`
- `resources/php/api/index.php`
- `resources/js/**`
- `page/**`
- `docs/API_REFERENCE.md`

Steps:

1. Confirm `check-hardcoded-api-paths.sh` passes.
2. Confirm API tests use canonical routes.
3. Replace direct scripts with explicit compatibility shims or delete them if no route uses them.
4. Update docs to list canonical routes first.
5. Keep removal of PHP-rendered business pages in a separate UI retirement slice.

Verification:

```bash
rtk bash scripts/checks/check-hardcoded-api-paths.sh
rtk vendor/bin/phpunit tests/Api
rtk npm --prefix apps/web run test
```

Stop conditions:

- a legacy page still calls the direct script.
- docs mention direct script URLs as the recommended API.
- deletion removes required images, templates, or compatibility assets.

Commit boundary:

- endpoint retirement and docs cleanup only.

### Slice 13: PHP-Rendered Frontend Retirement

Goal:

- Move business-page rendering to SvelteKit after API parity is proven.

Files:

- `apps/web/src/routes/dealer/**`
- `apps/web/src/routes/staff/**`
- `apps/web/src/lib/components/**`
- legacy pages under `page/**`
- legacy templates under `resources/templates/**`

Steps:

1. Verify each SvelteKit route has API-backed data loading or explicit fallback behavior.
2. Remove PHP-rendered business pages only after route parity exists.
3. Keep shared static assets in `apps/web/static` or another documented final location.
4. Do not port old DOM scripts file-for-file into Svelte components.
5. Keep route guards server-enforced through API checks.

Verification:

```bash
rtk npm --prefix apps/web run test
rtk npm --prefix apps/web run build
```

Stop conditions:

- a SvelteKit route needs data that has not been migrated to the API.
- page deletion would break an access point still documented in README or tests.
- UI work expands into backend schema changes.

Commit boundary:

- route migration or page retirement for one role/domain at a time.

### Slice 14: Final Verification and Documentation

Goal:

- Prove the migration is coherent and update docs to match the real runtime.

Files:

- `README.md`
- `docs/API_REFERENCE.md`
- `docs/architecture/**`
- `docs/schema/**`
- `docs/plans/**`
- `docs/testing/testing-plan.md`

Steps:

1. Update docs after behavior is verified, not before.
2. Make one recommended run path obvious.
3. Remove stale XAMPP/WAMP instructions if they conflict with Docker-first operation.
4. Document canonical API base URL, auth model, request envelope, error envelope, and local verification commands.
5. Run full verification or explain every skipped command.

Verification:

```bash
rtk vendor/bin/phpunit
rtk npm --prefix apps/web run test
rtk npm --prefix apps/web run build
rtk bash scripts/checks/verify-monorepo-structure.sh
rtk bash scripts/checks/check-hardcoded-api-paths.sh
```

Stop conditions:

- docs describe behavior not implemented in code.
- more than one run path is presented without a clear recommended default.
- full verification fails and the cause is not documented.

Commit boundary:

- docs and final verification fixes only.

## Parallel Agent Lane Rules

If multiple agents work at once, split by lane:

| Lane | Owns | Must not touch |
| --- | --- | --- |
| API contract | `resources/php/class/Api/**`, response tests | Svelte routes, Doctrine schema |
| Doctrine backend | `apps/api/**`, migrations, Doctrine tests | legacy page HTML, broad JS rewrites |
| Legacy compatibility | `resources/php/api/index.php`, endpoint shims | new Doctrine entities unless requested |
| Frontend client | `apps/web/src/lib/api/**`, endpoint config, frontend tests | backend services or DB |
| Docs/verifier | docs and scripts/checks | runtime behavior without explicit handoff |

Two agents must not edit the same file in the same slice. If that becomes necessary, stop and integrate deliberately.

## Verification Matrix

Run focused checks first:

```bash
rtk vendor/bin/phpunit tests/Unit/ApiBootstrapTest.php tests/Unit/ApiRequestTest.php tests/Unit/ApiAuthContextTest.php tests/Unit/RouteRegistryTest.php
rtk vendor/bin/phpunit tests/Unit/ResponseHandlerTest.php
rtk vendor/bin/phpunit tests/Api/AuthApiTest.php
rtk vendor/bin/phpunit tests/Api/SparePartApiTest.php
rtk vendor/bin/phpunit tests/Api/OrderApiTest.php
rtk vendor/bin/phpunit apps/api/tests
rtk npm --prefix apps/web run test
rtk bash scripts/checks/check-hardcoded-api-paths.sh
```

Run full checks before declaring the whole migration complete:

```bash
rtk vendor/bin/phpunit
rtk npm --prefix apps/web run build
rtk bash scripts/checks/verify-monorepo-structure.sh
```

If a command cannot run because dependencies, containers, or services are missing, record the exact blocker and do not claim it passed.

## Completion Criteria

The modernization is complete only when:

- all public API routes use `/api/v1` or the documented shared gateway
- all migrated endpoints return the canonical envelope
- frontend clients no longer call direct legacy PHP scripts
- auth and authorization are centralized server-side
- dealer routes are scoped to the authenticated dealer
- staff routes require sales-manager role or permission
- Doctrine migrations define the normalized schema
- public APIs use public ids, not internal numeric ids
- SvelteKit owns business-page rendering
- legacy direct endpoint scripts are removed or reduced to documented shims
- `docs/API_REFERENCE.md` documents canonical routes first
- focused backend tests pass
- focused frontend tests pass
- hardcoded endpoint path check passes

## Common Failure Patterns to Avoid

- Recreating existing `apps/api` or `apps/web` scaffolding instead of validating it.
- Adding a new endpoint script because it is faster than registering a route.
- Letting `/api/v1` and legacy aliases drift into different behavior.
- Changing response envelopes without adapting frontend parsing and tests.
- Trusting `userId`, `dealerID`, or `username` from request payloads for authorization.
- Migrating write endpoints before read endpoints and tests are stable.
- Mixing Doctrine schema work with UI page redesign.
- Deleting legacy files before endpoint config and tests prove no caller remains.
- Running a broad formatter that obscures the behavior change.
- Documenting the intended future as if it already works.

## Handoff Template for Implementation Agents

When handing off a slice, include:

```text
Slice:
Files changed:
Behavior changed:
Compatibility aliases kept:
Tests run:
Tests not run and why:
Known remaining legacy callers:
Next safe slice:
Stop conditions hit:
```

This template is required when a slice is incomplete or when multiple agents are coordinating.
