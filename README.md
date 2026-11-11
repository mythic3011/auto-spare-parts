# Auto Spare Parts

Dealer and staff portal for spare parts, orders, and shipping.

## Start here

Install Docker with Compose, then run from the repository root:

```sh
./project start
```

Open **http://127.0.0.1:8880**. Run `./project help` for the command list.
Startup creates `.env` only if absent, builds images, installs locked PHP
packages, and waits for service health. Existing `.env` files need a nonempty
`DB_DOCKER_PASSWORD` matching the existing database password.

```sh
./project preflight   # Check configuration and Docker
./project status      # Inspect health and published ports
./project logs        # Follow logs (Ctrl-C to exit)
./project test        # Run the NewApi unit suite against the running stack
./project stop        # Preserve database and dependency volumes
```

`WEB_PORT` and `WEB_BIND_ADDRESS` in `.env` control access. `ENV_FILE` selects
an alternate environment file. `BOOTSTRAP_TIMEOUT` sets the readiness timeout
(default 180 seconds, excluding builds). MariaDB and shipping stay internal.
Optional phpMyAdmin: `docker compose --profile tools up -d phpmyadmin`, then
open http://127.0.0.1:8090 and log in with database credentials.

The Compose project name is `auto-spare-parts`. Set `COMPOSE_PROJECT_NAME` in
`.env` to reuse an older project's volumes. Changing names does not migrate
volumes. The canonical fresh database seed is `sql-scripts/setup.sql`; it runs
only on an empty database volume. Startup never resets data or applies
migrations. Follow the [runtime contract](docs/api/runtime-contract.md) for
existing databases. `docker compose down -v` deletes persistent volumes.

## Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Dealer | `alex@auto-racing.com` | `DemoParts!2026` |
| Sales manager | `kit@slms.com` | `DemoParts!2026` |

On development/testing login pages, **Try a demo account** opens an overlay.
Double-click an email or password to copy it, use **Copy credentials** for both,
or select an account to fill the form. The overlay is absent in production.
Fresh databases receive the matching hashes from `sql-scripts/setup.sql`.
Existing database volumes are not reseeded by startup.

## Repository layout

| Path | Purpose / entrypoint |
| --- | --- |
| `project` | Main developer command; `./project help` |
| `docker-compose.yml`, `Dockerfile` | Local runtime and PHP image |
| `public/` | Nginx document root; explicit legacy entrypoint allowlist |
| `index.php`, `logout.php`, `logout.html` | Legacy web entries exposed through `public/` symlinks |
| `page/`, `resources/` | Active legacy pages, assets, PHP classes, and shipping service |
| `apps/api/public/index.php` | Versioned API gateway (`/api/v1/*`) |
| `resources/php/api/index.php` | Compatibility API gateway (`/api/*` excluding v1) |
| `apps/web/` | Svelte frontend under migration; not the default Compose web entry |
| `scripts/` | Init, preflight, bootstrap, and maintenance commands |
| `docker/` | Disposable validation stack |
| `sql-scripts/setup.sql` | Canonical fresh database schema and demo seed |
| `tests/` | Automated tests; `manual/` contains historical diagnostic scripts |
| `storage/` | Runtime media |
| `docs/` | Architecture, API, schema, plans, testing, and archived notes |
| `error/`, `template/`, `phototype/`, `tailwind.config.js` | Retained legacy templates, designs, and styling inputs |
| `bootstrap.php`, `composer.json`, `composer.lock`, `phpunit.xml` | PHP bootstrap, dependencies, and test configuration |

`public/index.php` points to the root `index.php`; the root login page remains
its source owner. Serve **only `public/`**, never the repository root.
The versioned API currently implements authentication; the complete Svelte
portal migration is pending. See the [runtime contract](docs/api/runtime-contract.md)
for implemented routes and isolated integration validation.

## Further reading

- [Runtime, checkout migration, and integration validation](docs/api/runtime-contract.md)
- [Browser checks, CSS build, and screenshot capture](docs/testing/browser-demo.md)
- [Audit fixes and reproducible validation](docs/testing/audit-fixes.md)
- [Testing plan](docs/testing/testing-plan.md)
- [Implementation plan](docs/IMPLEMENTATION_PLAN.md)
- [Historical setup notes](docs/archive/legacy-readme.md)

Historical helpers live in `scripts/legacy/`, `tests/manual/`, and
`sql-scripts/legacy/`. They are not the supported startup or migration flow.

<!-- DEMO_GALLERY_START -->
## Demo gallery

Real browser captures of the running legacy portal. Checkout captures use an
isolated seeded database; its loading capture holds the real request in flight.
Error-page HTTP statuses are intentional. Svelte migration targets and historical
prototypes are not part of the running portal.

<details>
<summary>Sign-in and information</summary>

**Sign in**

![Sign in](docs/screenshots/login.jpg)

**Demo account overlay**

![Demo account overlay](docs/screenshots/demo-accounts.jpg)

**Sign in — dark theme**

![Sign in — dark theme](docs/screenshots/login-dark.jpg)

**Privacy Policy**

![Privacy Policy](docs/screenshots/privacy-policy.jpg)

**Terms of Use**

![Terms of Use](docs/screenshots/terms-of-use.jpg)

**Q&A**

![Q&A](docs/screenshots/qa.jpg)

**About SLMC**

![About SLMC](docs/screenshots/about.jpg)

</details>

<details>
<summary>Dealer pages</summary>

**Spare parts catalog**

![Spare parts catalog](docs/screenshots/dealer-catalog.jpg)

**Part details**

![Part details](docs/screenshots/dealer-part.jpg)

**Shopping cart**

![Shopping cart](docs/screenshots/dealer-cart.jpg)

**Order history**

![Order history](docs/screenshots/dealer-orders.jpg)

**Order details**

![Order details](docs/screenshots/dealer-order.jpg)

**Profile**

![Profile](docs/screenshots/dealer-profile.jpg)

**Profile settings**

![Profile settings](docs/screenshots/dealer-profile-settings.jpg)

</details>

<details>
<summary>Staff pages</summary>

**Staff orders**

![Staff orders](docs/screenshots/staff-orders.jpg)

**Staff order details**

![Staff order details](docs/screenshots/staff-order.jpg)

**Catalog management**

![Catalog management](docs/screenshots/staff-items.jpg)

**Add item**

![Add item](docs/screenshots/staff-add-item.jpg)

**Edit item**

![Edit item](docs/screenshots/staff-edit-item.jpg)

**Reports**

![Reports](docs/screenshots/staff-report.jpg)

**Product deletion blocked by order history**

![Product deletion blocked by order history](docs/screenshots/staff-product-locked.jpg)

**Catalog search and combined filters**

![Catalog search and combined filters](docs/screenshots/staff-catalog-filtered.jpg)

**Catalog management — dark theme**

![Catalog management — dark theme](docs/screenshots/staff-items-dark.jpg)

**Staff orders — mobile**

![Staff orders — mobile](docs/screenshots/staff-orders-mobile.jpg)

**Catalog management — mobile**

![Catalog management — mobile](docs/screenshots/staff-items-mobile.jpg)

**Staff order details — mobile**

![Staff order details — mobile](docs/screenshots/staff-order-mobile.jpg)

**Add product — mobile**

![Add product — mobile](docs/screenshots/staff-add-item-mobile.jpg)

**Edit product — mobile**

![Edit product — mobile](docs/screenshots/staff-edit-item-mobile.jpg)

**Reports — mobile**

![Reports — mobile](docs/screenshots/staff-report-mobile.jpg)

</details>

<details>
<summary>Checkout states</summary>

**Order Summary with demo items**

![Order Summary with demo items](docs/screenshots/cart-populated.jpg)

**Placing the order**

![Placing the order](docs/screenshots/checkout-loading.jpg)

**Confirmation and redirect**

![Confirmation and redirect](docs/screenshots/checkout-confirmed.jpg)

</details>

<details>
<summary>Sign-out and error pages</summary>

**Error 403**

![Error 403](docs/screenshots/error-403.jpg)

**Error 404**

![Error 404](docs/screenshots/error-404.jpg)

**Error 500**

![Error 500](docs/screenshots/error-500.jpg)

**Error 503**

![Error 503](docs/screenshots/error-503.jpg)

**Sign out**

![Sign out](docs/screenshots/logout.jpg)

**Signed out**

![Signed out](docs/screenshots/logout-static.jpg)

</details>

[Capture inventory and HTTP results](docs/screenshots/manifest.json).

<!-- DEMO_GALLERY_END -->
