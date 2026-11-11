> This historical plan includes proposed tests and old setup examples. Use
> [audit fixes and reproducible validation](audit-fixes.md) for the current
> disposable database commands and verified test results.

# Auto Spare Parts - Comprehensive Testing Plan

## Overview

This document outlines a complete testing strategy for the Smart & Luxury Motor Company Auto Spare Parts Management System, covering unit tests, integration tests, API tests, and end-to-end testing.

---

## Table of Contents

1. [Testing Strategy](#testing-strategy)
2. [Test Environment Setup](#test-environment-setup)
3. [Unit Tests](#unit-tests)
4. [Integration Tests](#integration-tests)
5. [API Tests](#api-tests)
6. [Frontend Tests](#frontend-tests)
7. [Security Tests](#security-tests)
8. [Performance Tests](#performance-tests)
9. [Manual Testing Checklist](#manual-testing-checklist)
10. [Test Data](#test-data)

---

## Testing Strategy

### Testing Pyramid

```
           /\
          /  \    E2E Tests (10%)
         /____\
        /      \  Integration Tests (30%)
       /________\
      /          \ Unit Tests (60%)
     /____________\
```

### Test Coverage Goals

- **Unit Tests:** 80% code coverage
- **Integration Tests:** All critical user flows
- **API Tests:** 100% endpoint coverage
- **Security Tests:** OWASP Top 10 vulnerabilities
- **Performance Tests:** Response time < 500ms

---

## Test Environment Setup

### Prerequisites

```bash
# Install PHPUnit
composer require --dev phpunit/phpunit ^11.0

# Install testing dependencies
composer require --dev mockery/mockery
composer require --dev fakerphp/faker

# Create test database
mysql -u root -p -e "CREATE DATABASE projectdb_test;"
```

### Directory Structure

```
tests/
├── Unit/
│   ├── OrderTest.php
│   ├── ValidationHelperTest.php
│   ├── ResponseHandlerTest.php
│   ├── CartTest.php
│   ├── AuthTest.php
│   └── SparePartTest.php
├── Integration/
│   ├── OrderFlowTest.php
│   ├── AuthenticationFlowTest.php
│   └── CartCheckoutTest.php
├── Api/
│   ├── OrderApiTest.php
│   ├── UserApiTest.php
│   └── SparePartApiTest.php
├── Feature/
│   ├── DealerWorkflowTest.php
│   └── ManagerWorkflowTest.php
├── Fixtures/
│   ├── TestData.php
│   └── DatabaseSeeder.php
└── bootstrap.php
```

### PHPUnit Configuration

Create `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Api">
            <directory>tests/Api</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">resources/php/class</directory>
        </include>
        <exclude>
            <directory>vendor</directory>
            <directory>tests</directory>
        </exclude>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_HOST" value="localhost"/>
        <env name="DB_NAME" value="projectdb_test"/>
        <env name="DB_USER" value="root"/>
        <env name="DB_PASSWORD" value=""/>
    </php>
</phpunit>
```

---

## Unit Tests

### 1. Order Class Tests

**File:** `tests/Unit/OrderTest.php`

**Test Cases:**

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Order;
use Exception;

class OrderTest extends TestCase
{
    private Order $order;

    protected function setUp(): void
    {
        $this->order = new Order();
    }

    /** @test */
    public function it_generates_unique_order_id()
    {
        // Test order ID generation
    }

    /** @test */
    public function it_calculates_shipping_cost_correctly()
    {
        // Test shipping calculation
    }

    /** @test */
    public function it_validates_cart_items_before_order()
    {
        // Test cart validation
    }

    /** @test */
    public function it_throws_exception_for_insufficient_stock()
    {
        // Test stock validation
    }

    /** @test */
    public function it_assigns_sales_manager_randomly()
    {
        // Test sales manager assignment
    }

    /** @test */
    public function it_calculates_order_total_with_shipping()
    {
        // Test total calculation
    }

    /** @test */
    public function it_retrieves_dealer_orders_only()
    {
        // Test dealer order filtering
    }

    /** @test */
    public function it_updates_order_status_correctly()
    {
        // Test status updates
    }

    /** @test */
    public function it_cancels_order_successfully()
    {
        // Test order cancellation
    }

    /** @test */
    public function it_uses_default_shipping_cost_on_api_failure()
    {
        // Test fallback shipping cost
    }
}
```

### 2. ValidationHelper Tests

**File:** `tests/Unit/ValidationHelperTest.php`

**Test Cases:**

- `it_validates_required_fields()`
- `it_validates_email_format()`
- `it_validates_minimum_length()`
- `it_validates_maximum_length()`
- `it_validates_numeric_values()`
- `it_validates_min_max_values()`
- `it_validates_allowed_values()`
- `it_sanitizes_string_input()`
- `it_sanitizes_email_input()`
- `it_returns_all_errors()`

### 3. ResponseHandler Tests

**File:** `tests/Unit/ResponseHandlerTest.php`

**Test Cases:**

- `it_returns_success_response_with_200()`
- `it_returns_created_response_with_201()`
- `it_returns_error_response_with_400()`
- `it_returns_unauthorized_with_401()`
- `it_returns_not_found_with_404()`
- `it_returns_validation_error_with_422()`
- `it_includes_timestamp_in_response()`
- `it_formats_json_correctly()`

### 4. Cart Tests

**File:** `tests/Unit/CartTest.php`

**Test Cases:**

- `it_adds_item_to_cart()`
- `it_updates_item_quantity()`
- `it_removes_item_from_cart()`
- `it_calculates_cart_total()`
- `it_throws_exception_for_insufficient_stock()`
- `it_clears_cart_successfully()`
- `it_generates_unique_cart_id()`

### 5. Auth Tests

**File:** `tests/Unit/AuthTest.php`

**Test Cases:**

- `it_authenticates_valid_credentials()`
- `it_rejects_invalid_credentials()`
- `it_generates_authentication_token()`
- `it_validates_token_correctly()`
- `it_expires_old_tokens()`
- `it_logs_login_attempts()`
- `it_redirects_based_on_role()`

---

## Integration Tests

### 1. Order Flow Test

**File:** `tests/Integration/OrderFlowTest.php`

**Test Scenarios:**

```php
<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class OrderFlowTest extends TestCase
{
    /** @test */
    public function dealer_can_place_order_with_valid_cart()
    {
        // 1. Login as dealer
        // 2. Add items to cart
        // 3. Place order
        // 4. Verify order created
        // 5. Verify stock updated
        // 6. Verify shipping cost calculated
    }

    /** @test */
    public function order_placement_fails_with_insufficient_stock()
    {
        // Test stock validation during checkout
    }

    /** @test */
    public function order_updates_inventory_correctly()
    {
        // Test inventory reduction after order
    }

    /** @test */
    public function sales_manager_can_update_order_status()
    {
        // Test status update workflow
    }

    /** @test */
    public function dealer_can_cancel_pending_order()
    {
        // Test order cancellation
    }
}
```

### 2. Authentication Flow Test

**File:** `tests/Integration/AuthenticationFlowTest.php`

**Test Scenarios:**

- Complete login flow (credentials → token → session)
- Token expiration and renewal
- Logout and token invalidation
- Role-based access control
- 2FA flow (if implemented)

### 3. Cart Checkout Test

**File:** `tests/Integration/CartCheckoutTest.php`

**Test Scenarios:**

- Add multiple items to cart
- Update quantities
- Calculate totals with shipping
- Complete checkout
- Verify database transactions

---

## API Tests

### 1. Order API Tests

**File:** `tests/Api/OrderApiTest.php`

**Endpoints to Test:**

```php
<?php

namespace Tests\Api;

use PHPUnit\Framework\TestCase;

class OrderApiTest extends TestCase
{
    private string $baseUrl = 'http://localhost/resources/php/order_api.php';
    private string $token;

    protected function setUp(): void
    {
        // Get authentication token
        $this->token = $this->getAuthToken();
    }

    /** @test */
    public function get_dealer_order_list_returns_200()
    {
        $response = $this->get('/order_api.php?action=dealer-list');
        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('success', $response['body']['status']);
    }

    /** @test */
    public function get_order_without_auth_returns_401()
    {
        $response = $this->getWithoutAuth('/order_api.php?action=dealer-list');
        $this->assertEquals(401, $response['status_code']);
    }

    /** @test */
    public function place_order_with_valid_cart_returns_201()
    {
        $cart = [
            'cart' => [
                ['itemID' => '1', 'quantity' => 2, 'price' => 5000, 'weight' => 70]
            ]
        ];

        $response = $this->post('/order_api.php?action=place-order', $cart);
        $this->assertEquals(201, $response['status_code']);
        $this->assertArrayHasKey('id', $response['body']['data']);
    }

    /** @test */
    public function place_order_with_empty_cart_returns_400()
    {
        $response = $this->post('/order_api.php?action=place-order', ['cart' => []]);
        $this->assertEquals(400, $response['status_code']);
    }

    /** @test */
    public function update_order_status_with_invalid_status_returns_422()
    {
        $data = ['id' => '1', 'status' => 'InvalidStatus'];
        $response = $this->post('/order_api.php?action=update-status', $data);
        $this->assertEquals(422, $response['status_code']);
    }

    /** @test */
    public function get_order_details_returns_complete_data()
    {
        $response = $this->get('/order_api.php?action=dealer-get&id=1');

        $this->assertArrayHasKey('summary', $response['body']['data']);
        $this->assertArrayHasKey('items', $response['body']['data']);
        $this->assertArrayHasKey('totalCost', $response['body']['data']['summary']);
    }

    private function get(string $endpoint): array
    {
        // HTTP GET implementation
    }

    private function post(string $endpoint, array $data): array
    {
        // HTTP POST implementation
    }
}
```

### 2. API Response Format Tests

**Test Cases:**

- All success responses include `status`, `message`, `timestamp`, `data`
- All error responses include proper HTTP status codes
- Validation errors return field-level errors
- Timestamps are in correct format
- JSON is properly formatted

---

## Frontend Tests

### JavaScript Unit Tests (using Jest)

**Setup:**

```bash
npm init -y
npm install --save-dev jest @testing-library/dom
```

**Test Files:**

1. `tests/js/cart.test.js` - Cart functionality
2. `tests/js/auth.test.js` - Authentication
3. `tests/js/darkMode.test.js` - Dark mode toggle
4. `tests/js/api.test.js` - API calls

### Component Tests

**Test Cases:**

- Button component renders correctly
- Input component shows validation errors
- Modal opens and closes
- Alert dismisses correctly
- Table sorts data
- Dark mode persists in localStorage

---

## Security Tests

### 1. Authentication & Authorization

**Test Cases:**

- [ ] SQL Injection prevention (prepared statements)
- [ ] XSS prevention (output escaping)
- [ ] CSRF token validation
- [ ] Password hashing (bcrypt)
- [ ] Token expiration
- [ ] Session hijacking prevention
- [ ] Brute force protection (login attempts)

### 2. Input Validation

**Test Cases:**

- [ ] All user inputs are validated
- [ ] File upload restrictions
- [ ] SQL injection attempts blocked
- [ ] XSS payloads sanitized
- [ ] Command injection prevented

### 3. Access Control

**Test Cases:**

- [ ] Dealers can only see their orders
- [ ] Managers can see all orders
- [ ] Unauthorized API access blocked
- [ ] Direct file access prevented
- [ ] Role-based permissions enforced

---

## Performance Tests

### Load Testing (using Apache Bench)

```bash
# Test login endpoint
ab -n 1000 -c 10 -p login.json -T application/json http://localhost/resources/php/login.php

# Test order listing
ab -n 1000 -c 10 -H "Authorization: token" http://localhost/resources/php/order_api.php?action=dealer-list

# Test order placement
ab -n 100 -c 5 -p order.json -T application/json http://localhost/resources/php/order_api.php?action=place-order
```

### Performance Benchmarks

| Endpoint | Target | Acceptable | Poor |
|----------|--------|------------|------|
| Login | < 200ms | < 500ms | > 1s |
| Order List | < 100ms | < 300ms | > 500ms |
| Order Details | < 150ms | < 400ms | > 800ms |
| Place Order | < 300ms | < 700ms | > 1.5s |
| Stock Report | < 200ms | < 500ms | > 1s |

### Database Query Performance

**Test Cases:**

- Order listing query executes in < 50ms
- Order details query with JOINs < 30ms
- Stock check queries < 10ms
- No N+1 query problems

---

## Manual Testing Checklist

### Dealer Workflow

**Login & Authentication:**
- [ ] Login with valid credentials succeeds
- [ ] Login with invalid credentials fails
- [ ] Token is stored correctly
- [ ] Session persists across page refreshes
- [ ] Logout clears session and token
- [ ] Remember me checkbox works

**Browse Spare Parts:**
- [ ] All spare parts display correctly
- [ ] Images load properly
- [ ] Prices are formatted correctly
- [ ] Stock status shows accurately
- [ ] Search/filter works
- [ ] Pagination works (if implemented)

**Shopping Cart:**
- [ ] Add item to cart works
- [ ] Update quantity works
- [ ] Remove item works
- [ ] Cart total calculates correctly
- [ ] Cart persists across pages
- [ ] Empty cart shows appropriate message

**Place Order:**
- [ ] Order placement with valid cart succeeds
- [ ] Shipping cost calculates correctly
- [ ] Order confirmation displays
- [ ] Stock is reduced after order
- [ ] Order appears in order history
- [ ] Email notification sent (if implemented)

**View Orders:**
- [ ] Order list displays correctly
- [ ] Order details show complete information
- [ ] Order status is accurate
- [ ] Can cancel pending orders
- [ ] Cannot cancel shipped orders
- [ ] Order totals are correct

**Profile Management:**
- [ ] View profile information
- [ ] Update contact details
- [ ] Update delivery address
- [ ] Change password
- [ ] Profile changes persist

### Sales Manager Workflow

**Inventory Management:**
- [ ] View all spare parts
- [ ] Add new spare part
- [ ] Edit spare part details
- [ ] Update stock quantity
- [ ] Update prices
- [ ] Delete spare part (with validation)

**Order Management:**
- [ ] View all orders from all dealers
- [ ] Filter orders by status
- [ ] View order details
- [ ] Update order status
- [ ] Cannot cancel confirmed orders
- [ ] Order status changes reflect immediately

**Reports:**
- [ ] Generate stock report
- [ ] View low stock items
- [ ] Export reports (if implemented)
- [ ] Reports show accurate data

### UI/UX Testing

**Responsive Design:**
- [ ] Mobile (320px - 480px)
- [ ] Tablet (768px - 1024px)
- [ ] Desktop (1280px+)
- [ ] All components scale properly
- [ ] Navigation works on mobile

**Dark Mode:**
- [ ] Toggle switches between light/dark
- [ ] Preference persists in localStorage
- [ ] All components support dark mode
- [ ] Text is readable in both modes
- [ ] Images/icons adapt to theme

**Accessibility:**
- [ ] Keyboard navigation works
- [ ] Form labels are associated
- [ ] Error messages are clear
- [ ] Focus indicators visible
- [ ] Color contrast meets WCAG AA

**Browser Compatibility:**
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

---

## Test Data

### Test Users

```php
// Dealer Account
$dealerUser = [
    'username' => 'alex@auto-racing.com',
    'password' => 'DemoParts!2026',
    'role' => 'Dealer',
    'id' => 'alex@auto-racing.com'
];

// Sales Manager Account
$managerUser = [
    'username' => 'kit@slms.com',
    'password' => 'DemoParts!2026',
    'role' => 'SalesManager',
    'id' => 'kit@slms.com'
];
```

### Test Spare Parts

```php
$testItems = [
    [
        'sparePartNum' => 1,
        'sparePartName' => 'Vehicle Firewall',
        'price' => 5000,
        'stockItemQty' => 3,
        'weight' => 70
    ],
    [
        'sparePartNum' => 5,
        'sparePartName' => 'Headlight Bulb',
        'price' => 500,
        'stockItemQty' => 3,
        'weight' => 1
    ]
];
```

### Test Orders

```php
$testOrder = [
    'cart' => [
        [
            'itemID' => '1',
            'quantity' => 2,
            'price' => 5000,
            'weight' => 70
        ],
        [
            'itemID' => '5',
            'quantity' => 3,
            'price' => 500,
            'weight' => 1
        ]
    ]
];
```

---

## Test Execution Plan

### Phase 1: Unit Tests (Week 1)
- Day 1-2: Order class tests
- Day 3: ValidationHelper tests
- Day 4: ResponseHandler tests
- Day 5: Cart and Auth tests

### Phase 2: Integration Tests (Week 2)
- Day 1-2: Order flow tests
- Day 3: Authentication flow tests
- Day 4-5: Cart checkout tests

### Phase 3: API Tests (Week 3)
- Day 1-2: Order API tests
- Day 3: User API tests
- Day 4-5: Complete API coverage

### Phase 4: Manual & Security Tests (Week 4)
- Day 1-2: Manual testing checklist
- Day 3-4: Security testing
- Day 5: Performance testing

---

## Continuous Integration

### GitHub Actions Workflow

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mariadb:10.5
        env:
          MYSQL_ROOT_PASSWORD: rootpassword
          MYSQL_DATABASE: projectdb_test
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mysqli, pdo_mysql

      - name: Install dependencies
        run: composer install

      - name: Run tests
        run: vendor/bin/phpunit

      - name: Generate coverage
        run: vendor/bin/phpunit --coverage-html coverage
```

---

## Test Reporting

### Coverage Report

```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html coverage

# Generate text coverage report
vendor/bin/phpunit --coverage-text

# Generate Clover XML for CI
vendor/bin/phpunit --coverage-clover coverage.xml
```

### Test Results Format

```
PHPUnit 11.0.0 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.0
Configuration: phpunit.xml

...............................................................  63 / 150 ( 42%)
............................................................... 126 / 150 ( 84%)
........................                                        150 / 150 (100%)

Time: 00:02.456, Memory: 18.00 MB

OK (150 tests, 450 assertions)

Code Coverage Report:
  Classes: 85.00% (17/20)
  Methods: 82.50% (165/200)
  Lines:   80.25% (1605/2000)
```

---

## Success Criteria

### Minimum Requirements

- [ ] 80% unit test coverage
- [ ] All critical paths tested
- [ ] All API endpoints tested
- [ ] No security vulnerabilities
- [ ] Performance benchmarks met
- [ ] All manual tests pass

### Quality Gates

- [ ] All tests pass before merge
- [ ] No decrease in code coverage
- [ ] No new security issues
- [ ] Response times within limits
- [ ] Zero critical bugs

---

## Resources

### Testing Tools

- **PHPUnit:** https://phpunit.de/
- **Mockery:** http://docs.mockery.io/
- **Faker:** https://fakerphp.github.io/
- **Apache Bench:** https://httpd.apache.org/docs/2.4/programs/ab.html
- **OWASP ZAP:** https://www.zaproxy.org/

### Documentation

- PHPUnit Documentation
- Testing Best Practices
- OWASP Testing Guide
- API Testing Guide

---

**Last Updated:** 2026-03-05
**Version:** 1.0
**Status:** Ready for Implementation
