#!/usr/bin/env bash
# Reset only an explicitly selected, marked disposable Compose database.
set -euo pipefail

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"
: "${COMPOSE_FILE:?Set COMPOSE_FILE to the disposable stack definition}"
: "${COMPOSE_PROJECT_NAME:?Set COMPOSE_PROJECT_NAME to the disposable stack name}"
: "${TEST_DB_RESET:?Set TEST_DB_RESET=1 to reset the disposable test database}"
[[ "$TEST_DB_RESET" == 1 ]] || { echo 'TEST_DB_RESET must be 1' >&2; exit 1; }
export DB_TEST_NAME="${DB_TEST_NAME:-projectdb_test}"
[[ "$DB_TEST_NAME" =~ ^[A-Za-z0-9_]+_test$ ]] || { echo 'Database name must end in _test' >&2; exit 1; }
DB_TEST_USER="${DB_TEST_USER:-root}"
SQL_FILE="${TEST_DB_SCHEMA:-sql-scripts/setup.sql}"
[[ -r "$SQL_FILE" ]] || { echo 'Test schema is not readable' >&2; exit 1; }
compose=(docker compose -p "$COMPOSE_PROJECT_NAME" -f "$COMPOSE_FILE")
container="$("${compose[@]}" ps -q mariadb)"
[[ -n "$container" ]] || { echo 'Start the disposable MariaDB service first' >&2; exit 1; }
marker="$(docker inspect --format '{{ index .Config.Labels "com.auto-spare-parts.disposable" }}' "$container")"
[[ "$marker" == true ]] || { echo 'Refusing to reset an unmarked database container' >&2; exit 1; }

# The container owns its credential. Never print it or place it in argv.
mysql() {
    "${compose[@]}" exec -T mariadb sh -eu -c '
        export MYSQL_PWD="${MYSQL_ROOT_PASSWORD:?Missing container database credential}"
        exec mysql "$@"
    ' sh -u "$DB_TEST_USER" "$@"
}
ready=false
for ((attempt=0; attempt<30; attempt++)); do
    if mysql -Nse 'SELECT 1' >/dev/null 2>&1; then ready=true; break; fi
    sleep 1
done
[[ "$ready" == true ]] || { echo 'MariaDB did not become ready' >&2; exit 1; }
mysql -e "DROP DATABASE IF EXISTS \`$DB_TEST_NAME\`; CREATE DATABASE \`$DB_TEST_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
# These anchored statements belong to the canonical legacy SQL seed.
awk '/^CREATE DATABASE / { next } /^USE / { print "USE `" ENVIRON["DB_TEST_NAME"] "`;"; next } { print }' "$SQL_FILE" | mysql "$DB_TEST_NAME"
mysql "$DB_TEST_NAME" -e 'DELETE FROM OrdersItem; DELETE FROM Orders; DELETE FROM user_tokens; DELETE FROM login_attempts;'
for table in Dealer SalesManager Item Orders OrdersItem user_tokens login_attempts; do
    mysql "$DB_TEST_NAME" -Nse "SELECT COUNT(*) FROM \`$table\`" >/dev/null
done
for table in Dealer SalesManager Item; do
    count="$(mysql "$DB_TEST_NAME" -Nse "SELECT COUNT(*) FROM \`$table\`")"
    [[ "$count" -gt 0 ]] || { echo "Missing seed rows in $table" >&2; exit 1; }
done
printf 'Disposable test database %s is ready.\n' "$DB_TEST_NAME"
