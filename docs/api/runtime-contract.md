# Transitional runtime contract

Nginx serves `public/`. Its relative symlinks explicitly expose the legacy entry
pages and static assets without exposing the repository as a document root.
Private source directories, dotfiles, SQL, configuration and backup files are
unavailable. PHP files under static asset paths cannot execute. Existing legacy
PHP URLs resolve to their original scripts after checking the public allowlist.
New entry points must go through an API gateway rather than adding public scripts.

| Path | Runtime owner | Implemented behavior |
| --- | --- | --- |
| `/api/v1/*` | `apps/api/public/index.php` | Authentication only; unknown routes return 404 |
| `/api/v1/auth/login` | `AuthService` | POST email/password; returns token, user_public_id and role |
| `/api/v1/auth/me` | `AuthService` | GET with bearer token |
| `/api/v1/auth/logout` | `AuthService` | POST with bearer token; revokes token |
| `/api/*` excluding v1 | `resources/php/api/index.php` | Existing registry keys and compatibility aliases |

The v1 login success body is `{"success":true,"data":{"token":"…",
"user_public_id":"…","role":"dealer"}}`. The Svelte normalizer requires a
nonempty token and public identifier and a known role. Legacy login adapters
remain accepted. This identifier is never an authorization source.

Other Svelte domain routes are migration targets; their corresponding v1
handlers and complete cart/item editing workflows are still pending. V1 tokens
are not interchangeable with legacy tokens. Do not send v1 domain requests to
legacy handlers as a fallback. The canonical plan's route map describes the
target, while this table describes what is implemented and tested today.

## Checkout

The legacy cart accepts `itemID` and a positive integer `quantity` (existing ID
and quantity aliases remain compatible). Submitted price and weight fields are
ignored. Checkout rejects duplicates and missing items, locks catalog rows in a
consistent order, obtains price and weight from `Item`, and reserves stock in the
same transaction as the order and its lines. Prices are passed to persistence as
strings. Legacy monetary columns and display totals still use floating point;
conversion to DECIMAL columns and decimal arithmetic remains a separate migration.

MariaDB generates internal `Orders.orderID` values. `Orders.publicID` holds a
separate unique opaque identifier. Existing legacy URLs and response IDs retain
their numeric compatibility contract; future v1 orders must expose `publicID`.
Fresh installations use `sql-scripts/setup.sql`.

Existing databases must apply `Version20261111000100` before enabling checkout.
It preserves internal IDs, adds the primary key and AUTO_INCREMENT, backfills
public IDs, and enforces their uniqueness. Duplicate existing IDs cause migration
failure and require investigation; they are never repaired automatically.
From `apps/api`, with the deployment's database environment configured:

```sh
php ../../vendor/bin/doctrine-migrations execute 'DoctrineMigrations\Version20261111000100' --up
```

This targets only the legacy order migration. Do not apply the initial identity
schema over legacy tables without its separate identity migration plan. MySQL
DDL commits implicitly, so migration configuration disables all-or-nothing mode.
The migration deliberately has no destructive down operation.

`SHIPPING_SERVICE_URL` is the shipping service origin. Compose sets
`http://api:8080`; other deployments must configure their own origin. Checkout
uses the existing shipping client and requires valid accepted responses for both
weight and quantity quotes. Transport errors, rejected packages, malformed
responses or negative costs fail checkout and roll back all changes. There is
no implicit local estimate. Unavailable shipping returns HTTP 503 at checkout.

## Validation

The disposable stack has no host ports or persistent database volumes. Its
runtime scripts require `VALIDATION_STACK=true` and must never target real data.
Run from the repository root:

```sh
docker compose -p itp-review-validation -f docker/compose.validation.yml up -d --build
docker compose -p itp-review-validation -f docker/compose.validation.yml exec -T php php tests/Runtime/gateway-checkout.php
docker compose -p itp-review-validation -f docker/compose.validation.yml exec -T php php tests/Runtime/order-migration.php
docker compose -p itp-review-validation -f docker/compose.validation.yml down
php vendor/bin/phpunit --testsuite NewApi --no-logging
php vendor/bin/phpunit tests/Unit/CheckoutAuthorityTest.php --no-logging
```

The gateway script checks private-file rejection, static assets, legacy dispatch,
real v1 login/me/logout, authoritative pricing, concurrent checkout IDs and stock,
and rollback when shipping is unavailable. The migration script verifies existing
IDs, backfill, generated IDs and repeat execution on a disposable schema.
`phpunit.xml` includes `apps/api/tests` in the default suite.

The PHP image installs Composer dependencies from the committed lockfile, whose
platform is PHP 8.3. Frontend `npm ci` currently fails because its manifest/lockfile
are out of sync and include conflicting Svelte peers. For this fix, frontend tests
and build were validated using an unsaved installation with `--legacy-peer-deps`
and `@sveltejs/vite-plugin-svelte@7`; that does not repair reproducible frontend
installation. Resolve that dependency slice before relying on a clean frontend
build.

## Informational pages and checkout transition

`/privacy-policy.php`, `/terms-of-use.php`, `/qa.php`, and `/about.php` are
explicit Nginx routes to `resources/php/information.php`. They render mock
information through the shared template; no private source directory is exposed.

Checkout at `page/Dealer/shoppingCart.php` displays an in-flight overlay and
blocks repeat submissions. Only a committed order creates a one-use confirmation
in the server session, expiring after five minutes. The enhanced form receives a
fixed same-origin destination; ordinary form submission uses HTTP 303. The
confirmation at `page/Dealer/checkout.php` clears stale client cart views and
opens the placed order's details. A shipping failure returns HTTP 503 for the
enhanced form, restores the checkout control, and keeps the cart on screen.

Legacy global classes use Composer classmap autoloading. Only `App\Api\` is
PSR-4 mapped to `apps/api/src/`. Use `composer dump-autoload -o --strict-psr`
after changing classes. Do not add namespaces to legacy classes merely to make
them fit a PSR-4 mapping.

## Product deletion and order history

`SparePart` owns the product-deletion guard. Both single and batch deletion
lock selected catalog rows in ascending ID order, check current `OrdersItem`
references, and delete within one transaction. Checkout acquires those same
catalog locks before creating order lines. A referenced product raises
`ProductInOrderException`; the staff endpoint returns HTTP 409 with
`PRODUCT_IN_ORDER` and `lockedItemIds`. Mixed batches are all-or-nothing.
All order references, including fulfilled or cancelled orders, retain this
protection. Stock and price editing remain available.

The staff catalog exposes a lock badge and a modal explaining rejected deletion.
Order controls use the existing backend statuses: Pending, Confirmed, Shipped,
Delivered and Cancelled. Historical Processing orders can be explicitly moved
to Shipped using the staff UI; no existing status is rewritten automatically.
