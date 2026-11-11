#!/bin/bash

# Auto Spare Parts - Quick Start Script
# This script sets up the testing environment and runs initial tests

set -e  # Exit on error

# Preserve repository-relative paths after relocation.
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.."

echo "=========================================="
echo "Auto Spare Parts - Testing Setup"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo -e "${RED}Error: Composer is not installed${NC}"
    echo "Please install Composer from https://getcomposer.org/"
    exit 1
fi

echo -e "${GREEN}✓ Composer found${NC}"

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓ PHP ${PHP_VERSION} found${NC}"

# Check PHP version (need 8.3+)
if php -r "exit(version_compare(PHP_VERSION, '8.3.0', '<') ? 1 : 0);"; then
    echo -e "${YELLOW}Warning: PHP 8.3+ recommended, you have ${PHP_VERSION}${NC}"
fi

# Install Composer dependencies
echo ""
echo "Installing Composer dependencies..."
if [ ! -d "vendor" ]; then
    composer install
    echo -e "${GREEN}✓ Dependencies installed${NC}"
else
    echo -e "${YELLOW}Vendor directory exists, skipping install${NC}"
    echo "Run 'composer update' to update dependencies"
fi

# Check if MySQL is available
echo ""
echo "Checking database connection..."
if command -v mysql &> /dev/null; then
    echo -e "${GREEN}✓ MySQL client found${NC}"

    # Prompt for database setup
    read -p "Do you want to create the test database? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Creating test database..."
        read -p "MySQL root password: " -s MYSQL_PASSWORD
        echo

        mysql -u root -p"${MYSQL_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS projectdb_test;" 2>/dev/null
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Test database created${NC}"

            # Import schema
            if [ -f "sql-scripts/legacy/setup.sql" ]; then
                echo "Importing database schema..."
                mysql -u root -p"${MYSQL_PASSWORD}" projectdb_test < sql-scripts/legacy/setup.sql 2>/dev/null
                echo -e "${GREEN}✓ Schema imported${NC}"
            fi
        else
            echo -e "${RED}✗ Failed to create database${NC}"
        fi
    fi
else
    echo -e "${YELLOW}MySQL client not found, skipping database setup${NC}"
fi

# Create necessary directories
echo ""
echo "Creating test directories..."
mkdir -p tests/Unit
mkdir -p tests/Integration
mkdir -p tests/Api
mkdir -p tests/Feature
mkdir -p tests/Fixtures
mkdir -p coverage
echo -e "${GREEN}✓ Directories created${NC}"

# Check if Docker is running (optional)
echo ""
if command -v docker &> /dev/null; then
    if docker info &> /dev/null; then
        echo -e "${GREEN}✓ Docker is running${NC}"

        read -p "Do you want to start Docker containers? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            echo "Starting Docker containers..."
            docker compose up -d
            echo -e "${GREEN}✓ Containers started${NC}"
            echo "Services available at:"
            echo "  - Web: http://localhost"
            echo "  - phpMyAdmin: http://localhost:8090"
            echo "  - API: http://localhost:8080"
        fi
    else
        echo -e "${YELLOW}Docker is installed but not running${NC}"
    fi
else
    echo -e "${YELLOW}Docker not found, skipping container setup${NC}"
fi

# Run initial tests
echo ""
echo "=========================================="
echo "Running Initial Tests"
echo "=========================================="
echo ""

if [ -f "vendor/bin/phpunit" ]; then
    echo "Running unit tests..."
    vendor/bin/phpunit tests/Unit/ --testdox || true

    echo ""
    echo "=========================================="
    echo "Test Summary"
    echo "=========================================="
    vendor/bin/phpunit --version
    echo ""

    # Generate coverage report
    read -p "Generate code coverage report? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Generating coverage report..."
        vendor/bin/phpunit --coverage-html coverage/ --coverage-text
        echo -e "${GREEN}✓ Coverage report generated in coverage/${NC}"
        echo "Open coverage/index.html in your browser"
    fi
else
    echo -e "${RED}PHPUnit not found. Run 'composer install' first.${NC}"
fi

# Summary
echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Review test results above"
echo "2. Run 'vendor/bin/phpunit' to run all tests"
echo "3. Run 'vendor/bin/phpunit --coverage-html coverage/' for coverage"
echo "4. Check docs/testing/testing-plan.md for detailed testing guide"
echo "5. Check docs/IMPLEMENTATION_PLAN.md for project roadmap"
echo ""
echo "Useful commands:"
echo "  - Run all tests: vendor/bin/phpunit"
echo "  - Run unit tests: vendor/bin/phpunit tests/Unit/"
echo "  - Run with coverage: vendor/bin/phpunit --coverage-text"
echo "  - Run specific test: vendor/bin/phpunit tests/Unit/OrderTest.php"
echo ""
echo -e "${GREEN}Happy testing! 🚀${NC}"
