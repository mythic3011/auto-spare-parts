> Historical reference. Paths and commands may be outdated. Use the root
> [README](../../README.md) and `./project help` for the current entrypoints.

# Session Resume — ITP4523M Modernization

**Last updated:** 2026-03-10
**Status:** Testing infrastructure complete, 121/121 unit+integration tests passing, 1 cleanup task remaining

---

## What This Project Is

A full modernization of the original ITP4523M PHP project (Smart & Luxury Motor Company Auto Spare Parts Management System). This is intended to be a **new separate repository** — not a branch of the original.

---

## What Was Done This Session

### 1. Project Review
- Full codebase audit (PHP 8.3, MariaDB, Nginx, Flask, Docker/OrbStack)
- Identified architecture: Dealer + SalesManager roles, spare parts CRUD, order management, shipping API

### 2. Core PHP Fixes
| File | Change |
|------|--------|
| `resources/php/class/config.php` | Auto-detect Docker via `gethostbyname('mariadb')`, `.env` no longer overwrites pre-set env vars |
| `resources/php/class/Database.php` | Now uses `$config` array from `config.php` instead of hardcoded credentials; added `fetchAll()`, `fetchOne()`, `execute()` |
| `resources/php/class/Auth.php` | `getBearerToken()` no longer depends on `apache_request_headers()` (works in CLI/PHPUnit) |
| `resources/php/class/Order.php` | Fixed `d.CompanyName` → `d.dealerName`; cast `orderID` to string at call sites |
| `sql-scripts/setup.sql` | Added `PRIMARY KEY (orderID, sparePartNum)` to `OrdersItem` |

### 3. Testing Infrastructure Created
```
tests/
├── bootstrap.php               # Sets env vars BEFORE config loads, loads all classes
├── Unit/
│   ├── AuthTest.php            # 13 tests
│   ├── CartTest.php            # 12 tests
│   ├── OrderTest.php           # 13 tests
│   ├── ResponseHandlerTest.php # 9 tests (uses subprocess to handle exit())
│   ├── SparePartTest.php       # 11 tests
│   ├── UserTest.php            # 12 tests
│   └── ValidationHelperTest.php# 21 tests
├── Integration/
│   ├── AuthenticationFlowTest.php  # 10 tests
│   ├── CartCheckoutTest.php        # 7 tests
│   └── OrderFlowTest.php           # 12 tests
└── Api/
    ├── AuthApiTest.php         # 5 tests (requires running server)
    ├── OrderApiTest.php        # 15 tests (requires running server)
    └── SparePartApiTest.php    # 5 tests (requires running server)

phpunit.xml                     # PHPUnit 11 config
composer.json                   # Fixed: removed non-existent src/helpers.php
scripts/setup-test-db.sh        # Docker-aware test DB setup script
```

### 4. Test Results (last run)
```
Tests: 121, Assertions: 239
✔ 121 passing
✘ 0 failing
⚠ 2 warnings  (session_regenerate_id in CLI — harmless)
⚠ 121 PHPUnit deprecations (@test annotation → needs #[Test] attribute)
```

---

## Remaining Tasks

### Immediate (next session)

#### Task 1 — Fix PHPUnit deprecations (15 min)
Replace `/** @test */` doc-comment annotations with `#[Test]` PHP 8 attributes across all test files.

```bash
# The sed command that was interrupted:
find tests -name "*.php" -not -name "bootstrap.php" | while read f; do
  sed -i '' 's|/\*\* @test \*/|#[Test]|g' "$f"
  sed -i '' 's|use PHPUnit\\Framework\\TestCase;|use PHPUnit\\Framework\\Attributes\\Test;\nuse PHPUnit\\Framework\\TestCase;|g' "$f"
done
```

Then verify:
```bash
docker compose up -d
bash scripts/setup-test-db.sh
docker exec itp4523m_project-php-1 vendor/bin/phpunit tests/Unit/ tests/Integration/ --no-progress
```

**Expected result:** 121 tests, 0 deprecations, 0 warnings

#### Task 2 — Fix UserTest missing methods (30 min)
`User.php` is missing methods the tests expect. Either add them or update the tests to match what exists.

Missing methods in `User.php`:
- `getUser(string $userId)` — returns user row from UserRoles
- `getRoleByUserId(string $userId)` — returns role string
- `verifyPassword(string $plain, string $hash)` — wraps `password_verify()`
- `isUserLocked(string $username)` — checks login_attempts count

#### Task 3 — New Git repo setup (10 min)
```bash
# In a new directory:
git init ITP4523M_Modernized
cp -r ITP4523M_Project/. ITP4523M_Modernized/
cd ITP4523M_Modernized
rm -rf .git   # remove old git history
git init
git add .
git commit -m "Initial commit: modernized ITP4523M project"
# Push to new GitHub repo
gh repo create ITP4523M_Modernized --public
git remote add origin <new-repo-url>
git push -u origin main
```

#### Task 4 — API tests (1 hour)
The `tests/Api/` tests require a running HTTP server. Run them via:
```bash
docker compose up -d
docker exec itp4523m_project-php-1 vendor/bin/phpunit tests/Api/ --no-progress
```
These will likely need fixes for the same reasons as unit/integration tests.

### Later

#### Task 5 — Frontend page migration (2 weeks)
12 HTML pages in `page/Dealer/` and `page/Staff/` still need converting to PHP with the new component system.
See `IMPLEMENTATION_PLAN.md` Phase 4 for details.

#### Task 6 — Security hardening
- CSRF validation on all POST endpoints
- Rate limiting on login
- Input sanitization audit

#### Task 7 — CI/CD
Add `.github/workflows/tests.yml` to run PHPUnit on every push.

---

## How to Resume

### Start Docker
```bash
cd /Users/mythic3014/Documents/project/ITP4523M_Project
docker compose up -d
```

### Setup test DB (first time or after reset)
```bash
bash scripts/setup-test-db.sh
```

### Install Composer deps (if vendor/ missing)
```bash
docker exec itp4523m_project-php-1 composer install
```

### Run tests
```bash
# Unit + Integration
docker exec itp4523m_project-php-1 vendor/bin/phpunit tests/Unit/ tests/Integration/ --testdox --no-progress

# All tests
docker exec itp4523m_project-php-1 vendor/bin/phpunit --no-progress
```

---

## Key Architecture Decisions Made

1. **`config.php` is the single source of truth** for DB config — `Database.php` just reads from it
2. **Auto-detect Docker** via `gethostbyname('mariadb')` — no manual `DOCKER_ENV` flag needed in production
3. **Test env vars set before any `require_once`** — prevents `.env` from overwriting test DB name
4. **`OrdersItem` has composite PK** `(orderID, sparePartNum)` — prevents duplicate rows
5. **Test DB has no transactional seed data** — `setup-test-db.sh` truncates Orders/OrdersItem after import
6. **`resetTestDatabase()`** truncates transactional tables AND restores item stock levels

---

## File Locations
- Project root: `/Users/mythic3014/Documents/project/ITP4523M_Project/`
- Test DB: `projectdb_test` (in Docker MariaDB container)
- Prod DB: `projectDB` (in Docker MariaDB container)
- phpMyAdmin: http://localhost:8090
- App: http://localhost (nginx → php-fpm)
- Shipping API: http://localhost:8080
