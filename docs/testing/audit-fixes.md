# Audit fixes and validation

The audit of initial public commit `f2a0eef` identified broken staff printing,
frontend dependency conflicts/advisories, unsafe test-database setup behavior,
a failing order fixture and session warnings. These fixes preserve the classic
Compose portal as the default app.

## Changes

- Staff printing calls the browser print operation without replacing the body.
  Print CSS retains the receipt and hides workspace controls. The browser
  regression verifies DOM identity, working theme listeners and print layout.
- Test DB setup requires a selected Compose file/project, `TEST_DB_RESET=1`, a
  name ending in `_test`, and a database container explicitly marked disposable.
  It uses the container's credential without printing it; readiness and schema
  import failures stop the script. Explicit test connection settings survive
  PHPUnit bootstrap; destructive test resets validate the database name.
- The order item-count test uses catalog products whose combined weight fits the
  real shipping service. The session starts before PHPUnit writes output.
- PHPStan has a versioned level-5 configuration and Doctrine reflection support.
  Stale return contracts, inaccurate PHPDoc, dead branches and redundant checks
  were corrected without a suppression baseline.
- Unused frontend libraries were removed after checking source/config imports.
  Remaining dependencies are pinned. SvelteKit 3 config, native `#lib` subpath
  imports, page state and Svelte props were updated following the
  [upstream migration guide](https://svelte.dev/docs/kit/migrating-to-sveltekit-3).
  The lockfile supports clean installation without peer-dependency bypasses.
- Symfony development dependencies were patched. The CSS tools pin a compatible
  patched selector parser; the committed CSS is generated from canonical input.

## Reproduce backend checks

From the repository root, use a disposable stack:

```sh
docker compose -p asp-validation -f docker/compose.validation.yml up -d --build
COMPOSE_FILE=docker/compose.validation.yml COMPOSE_PROJECT_NAME=asp-validation TEST_DB_RESET=1 ./scripts/setup-test-db.sh
docker compose -p asp-validation -f docker/compose.validation.yml exec -T php composer validate --no-check-publish
docker compose -p asp-validation -f docker/compose.validation.yml exec -T php composer dump-autoload --optimize --strict-psr
docker compose -p asp-validation -f docker/compose.validation.yml exec -T php php vendor/bin/phpunit --testsuite Unit,NewApi --no-logging --display-warnings
docker compose -p asp-validation -f docker/compose.validation.yml exec -T php php vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose -p asp-validation -f docker/compose.validation.yml exec -T php composer audit --locked
docker compose -p asp-validation -f docker/compose.validation.yml down
```

The validation database uses tmpfs and exposes no host database port. The
ordinary development database is deliberately unmarked and cannot be selected
by the reset helper. `TEST_DB_SCHEMA` can select a test schema file; malformed
SQL must return nonzero and must not print the ready message. This failure path,
the successful canonical import, missing reset opt-in and a non-test database
name were exercised on a disposable stack.

## Reproduce frontend checks

Node 22.17+ is required by SvelteKit; the selected Vitest also requires Node
22.12+, 24, or 26+. Node 24 was used for verification.

```sh
npm --prefix apps/web ci --ignore-scripts
npm --prefix apps/web run check
npm --prefix apps/web test
npm --prefix apps/web run build
npm --prefix apps/web audit
npm --prefix resources/styles ci --ignore-scripts
npm --prefix resources/styles run build
npm --prefix resources/styles audit
```

Verified: PHP Unit + NewApi **127 tests / 265 assertions**, no warnings; PHPStan
**zero errors**; strict autoload and Composer validation pass. Frontend
**23 tests pass**, type/component checks have **zero errors and warnings**, and
the production build completes. Printing passed in real headless Chrome.
Composer and CSS dependency audits report zero advisories.

## Remaining upstream advisory

The frontend tooling audit reports **four low-severity affected packages** in
one dependency chain: adapter-auto → SvelteKit → vite-plugin-svelte → deepmerge.
The actual advisory is
[GHSA-gcqx-w48m-w2h4](https://github.com/advisories/GHSA-gcqx-w48m-w2h4).
Upstream lists no patched deepmerge release. No audit suppression, forced downgrade
or custom vendored replacement was applied. There are no moderate, high or
critical frontend audit findings in the regenerated lockfile. This is a
build-tooling dependency assessment, not a claim of production acceptance.

The Svelte app still has domain migration placeholders and needs its corresponding
v1 API endpoints; a clean build does not imply those workflows are complete.
