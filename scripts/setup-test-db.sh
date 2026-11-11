#!/bin/bash

# Setup test database in Docker container
# Usage: ./scripts/setup-test-db.sh

set -e

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"
DB_TEST="projectdb_test"
DB_USER="root"
DB_PASS="rootpassword"
SQL_FILE="sql-scripts/setup.sql"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=========================================="
echo " Auto Spare Parts - Test Database Setup"
echo "=========================================="

# Check Docker is running
if ! docker info &>/dev/null; then
    echo -e "${RED}✗ Docker / OrbStack is not running${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker running${NC}"
CONTAINER="$(docker compose ps -q mariadb)"

# Check container is up
if ! docker compose ps --status running -q mariadb | grep -q .; then
    echo -e "${YELLOW}⚠ MariaDB container not running, starting...${NC}"
    docker compose up -d --wait mariadb
    CONTAINER="$(docker compose ps -q mariadb)"
    echo "  Waiting for MariaDB to be ready..."
    sleep 5
fi
echo -e "${GREEN}✓ MariaDB container: ${CONTAINER}${NC}"

# Wait until MariaDB accepts connections
echo "  Checking MariaDB connection..."
for i in {1..15}; do
    if docker exec "$CONTAINER" mysqladmin -u"$DB_USER" -p"$DB_PASS" ping --silent 2>/dev/null; then
        break
    fi
    echo "  Waiting... ($i/15)"
    sleep 2
done
echo -e "${GREEN}✓ MariaDB accepting connections${NC}"

# Drop and recreate test database
echo ""
echo "Creating test database '${DB_TEST}'..."
docker exec "$CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" -e "
    DROP DATABASE IF EXISTS \`${DB_TEST}\`;
    CREATE DATABASE \`${DB_TEST}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
" 2>/dev/null
echo -e "${GREEN}✓ Database '${DB_TEST}' created${NC}"

# Import schema — strip CREATE DATABASE, fix USE, and remove transactional seed data
echo "Importing schema from ${SQL_FILE}..."
grep -v 'CREATE DATABASE' "$SQL_FILE" \
    | sed "s/USE \`[^\`]*\`/USE \`${DB_TEST}\`/g" \
    | docker exec -i "$CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_TEST" 2>&1 \
    | grep -v "^$" \
    | grep -v "ERROR 1062" \
    || true

# Remove transactional seed data — tests manage their own Orders/OrdersItem
echo "Clearing transactional seed data..."
docker exec "$CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_TEST" -e "
    TRUNCATE TABLE Orders;
    TRUNCATE TABLE OrdersItem;
    TRUNCATE TABLE user_tokens;
    TRUNCATE TABLE login_attempts;
" 2>/dev/null
echo -e "${GREEN}✓ Schema imported${NC}"

# Verify tables
echo ""
echo "Verifying tables..."
TABLES=$(docker exec "$CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_TEST" -e "SHOW TABLES;" 2>/dev/null | tail -n +2)
TABLE_COUNT=$(echo "$TABLES" | grep -c . || true)

if [ "$TABLE_COUNT" -lt 5 ]; then
    echo -e "${RED}✗ Expected at least 5 tables, found ${TABLE_COUNT}${NC}"
    echo "$TABLES"
    exit 1
fi

echo -e "${GREEN}✓ ${TABLE_COUNT} tables found:${NC}"
echo "$TABLES" | sed 's/^/    /'

# Verify seed data
echo ""
echo "Verifying seed data..."
DEALERS=$(docker exec "$CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_TEST" -se "SELECT COUNT(*) FROM Dealer;" 2>/dev/null)
ITEMS=$(docker exec "$CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_TEST" -se "SELECT COUNT(*) FROM Item;" 2>/dev/null)
MANAGERS=$(docker exec "$CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_TEST" -se "SELECT COUNT(*) FROM SalesManager;" 2>/dev/null)

echo "    Dealers:       ${DEALERS}"
echo "    Items:         ${ITEMS}"
echo "    SalesManagers: ${MANAGERS}"

if [ "$DEALERS" -lt 1 ] || [ "$ITEMS" -lt 1 ] || [ "$MANAGERS" -lt 1 ]; then
    echo -e "${RED}✗ Seed data missing${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Seed data verified${NC}"

echo ""
echo "=========================================="
echo -e "${GREEN} Test database ready!${NC}"
echo "=========================================="
echo ""
echo "  Host:     mariadb (Docker) / 127.0.0.1:3306 (host)"
echo "  Database: ${DB_TEST}"
echo "  User:     ${DB_USER}"
echo "  Password: ${DB_PASS}"
echo ""
echo "Run tests with:"
echo "  docker compose exec php vendor/bin/phpunit"
echo ""
