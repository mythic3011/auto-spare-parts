# Browser demo and screenshots

Start the local portal with `./project start`. The supported browser workflows
are the legacy dealer and staff pages served by Compose, plus the four mock
information pages. The Svelte app remains a separate migration target.

The public demo-account fixture is `resources/php/class/DemoAccounts.php`.
`DemoAccountsTest` checks that the six fresh SQL seed hashes match the displayed
password. The login overlay is only rendered in development/testing.

## Build portal styles

The browser loads generated `resources/css/portal.css`, with no Tailwind CDN
runtime. Its source, theme, pinned dependencies and lockfile live together:

```sh
npm --prefix resources/styles ci --ignore-scripts
npm --prefix resources/styles run build
```

The root `tailwind.config.js` is a compatibility pointer to that theme. Commit
the generated stylesheet with source changes so PHP startup needs no Node toolchain.
The frontend owns the five SLMC branding images; legacy image URLs are relative
symlinks to those files.

## Browser checks and gallery

Install Google Chrome, Node.js, and PHP locally, then:

```sh
npm --prefix scripts/demo ci --ignore-scripts
npm --prefix scripts/demo run check:login
npm --prefix scripts/demo run capture
node scripts/demo/gallery.mjs
```

`DEMO_BASE_URL` overrides the default `http://127.0.0.1:8880`.
`DEMO_BROWSER_CHANNEL` overrides the default Playwright `chrome` channel.
The login checks cover copying credentials, filling the form, persistent and
session-only login, profile/settings, dark mode, malformed order links and
server rejection of invalid IDs. Screenshots are real browser output;
`docs/screenshots/manifest.json` records routes and HTTP/asset failures.

## Disposable checkout check

Never run this check against an existing database. It creates orders and stops
the shipping service. Use a separate generated environment and project:

```sh
review_dir=$(mktemp -d)
ENV_FILE="$review_dir/config" ./project init
ENV_FILE="$review_dir/config" WEB_PORT=18880 COMPOSE_PROJECT_NAME=asp-checkout-review ./project start
DEMO_BASE_URL=http://127.0.0.1:18880 CHECKOUT_TEST_ENV_FILE="$review_dir/config" CHECKOUT_TEST_PROJECT=asp-checkout-review npm --prefix scripts/demo run check:checkout
# Remove only this disposable project's services and volumes.
docker compose --env-file "$review_dir/config" -p asp-checkout-review down --volumes
```

The check captures a populated cart, the actual in-flight overlay, and the
confirmation page; verifies one submission and refresh-safe redirection; then
stops the real shipping service and verifies a recoverable checkout failure.
The in-flight request is delayed only to capture the loading state; no success
response is mocked. Run it on a fresh disposable volume for repeatable stock.
The ordinary form path also retains HTTP 303 for progressive enhancement.

## Staff workflows and product deletion

Use a fresh disposable stack on port 18881:

```sh
staff_review_dir=$(mktemp -d)
ENV_FILE="$staff_review_dir/config" WEB_PORT=18881 COMPOSE_PROJECT_NAME=asp-staff-review ./project start
docker compose --env-file "$staff_review_dir/config" -p asp-staff-review exec -T -e STAFF_TEST_PROJECT=asp-staff-review -e DB_QUERY_CACHE=false php php tests/Runtime/product-deletion-lock.php
DEMO_BASE_URL=http://127.0.0.1:18881 STAFF_TEST_PROJECT=asp-staff-review npm --prefix scripts/demo run check:staff
docker compose --env-file "$staff_review_dir/config" -p asp-staff-review down --volumes
```

The runtime check creates a Pending order using a real shipping request while
concurrently attempting product deletion. It verifies that deletion waits for
the checkout lock, then rejects the committed reference. Run it before the
browser check, which uses that Pending order. Start with fresh volumes for
repeatable seeded Processing-order coverage.

The browser check exercises real add/edit forms with decimal prices; direct
single and mixed-batch deletion rejection; lock and deletion modals; successful
unreferenced-product deletion; Pending and historical Processing order flows;
report loading and CSV export; and dealer rejection at the staff deletion API.
`staff-workflow-results.json` contains the passing checks from the latest run.

## Staff layout validation

`npm --prefix scripts/demo run check:staff-layout` checks all six staff pages
at 1440px and 390px widths, verifies one main region and active navigation,
checks mobile menu open/Escape behavior and light/dark switching, and rejects
page-level horizontal overflow and uncaught browser errors. It refreshes the
staff desktop/mobile screenshots and writes `staff-layout-results.json`.

The staff shell is shared by the six HTML pages. Structural styling lives in
`resources/css/staff.css`; responsive navigation behavior lives in
`resources/js/staff-shell.global.js`. The existing page-specific form/table IDs
remain the workflow integration points.

## Staff search and filters

`npm --prefix scripts/demo run check:staff-filters` verifies catalog search,
category/stock/order-protection combinations, order status and inclusive date
ranges, invalid ranges, report search/sales filters, empty results, reset,
filtered CSV export, and mobile overflow. Search is case insensitive and applies
to the loaded records. Hidden catalog rows are deselected, so batch actions cannot
silently affect filtered-out products. Select all applies only to visible rows.

Catalog search covers ID, name and description. Orders cover ID, sales manager,
dealer and delivery address; the date range applies to the order date. Reports
cover part ID, part name and associated order numbers. Counts show matching and
loaded totals; filters update when the existing table reloads or sorts.

## Order printing regression

`npm --prefix scripts/demo run check:print` opens a seeded staff order and checks
that printing preserves the page DOM and theme listeners, while print CSS hides
navigation and actions. `PRINT_TEST_ORDER_ID` selects an existing order and
`DEMO_BASE_URL` selects the running portal. It intercepts the print dialog and
checks the browser's print media layout without changing order data.
