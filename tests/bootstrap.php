<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File
 *
 * Sets up the testing environment
 */

// Define test environment — must be set BEFORE any require_once loads config.php
define('TESTING', true);
putenv('APP_ENV=testing');
putenv('APP_DEBUG=false');
putenv('DOCKER_ENV=true');
putenv('DB_DOCKER_HOST=mariadb');
putenv('DB_DOCKER_USER=root');
putenv('DB_DOCKER_PASSWORD=rootpassword');
putenv('DB_DOCKER_NAME=projectdb_test');

require_once __DIR__ . '/../apps/api/tests/bootstrap.php';

// Load all project classes (they use require_once, not PSR-4)
$classDir = __DIR__ . '/../resources/php/class/';
require_once $classDir . 'config.php';
require_once $classDir . 'RoleName.php';
require_once $classDir . 'HttpJson.php';
require_once $classDir . 'AppEnvironment.php';
require_once $classDir . 'RequestToken.php';
require_once $classDir . 'AuthFlow.php';
require_once $classDir . 'UploadedImage.php';
require_once $classDir . 'Database.php';
require_once $classDir . 'Session.php';
require_once $classDir . 'CSRF.php';
require_once $classDir . 'Auth.php';
require_once $classDir . 'User.php';
require_once $classDir . 'SparePart.php';
require_once $classDir . 'Cart.php';
require_once $classDir . 'Shipping.php';
require_once $classDir . 'Report.php';
require_once $classDir . 'ValidationHelper.php';
require_once $classDir . 'ResponseHandler.php';
require_once $classDir . 'TemplateRenderer.php';
require_once $classDir . 'Order.php';
require_once $classDir . 'Api/ApiBootstrap.php';
require_once $classDir . 'Api/ApiRequest.php';
require_once $classDir . 'Api/ApiAuthContext.php';
require_once $classDir . 'Api/ApiResponder.php';
require_once $classDir . 'Api/RouteRegistry.php';

// Error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Disable output buffering during tests
if (ob_get_level()) {
    ob_end_clean();
}

// Helper function to reset database
function resetTestDatabase(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
    }
    if (method_exists('Cart', 'resetStateForTesting')) {
        Cart::resetStateForTesting();
    }

    $db = Database::getInstance();

    // Truncate transactional tables
    $tables = ['Orders', 'OrdersItem', 'user_tokens', 'login_attempts'];
    foreach ($tables as $table) {
        $db->query("TRUNCATE TABLE {$table}");
    }

    // Restore seed stock levels so each test starts with known quantities
    $db->query("UPDATE Item SET stockItemQty = 3  WHERE sparePartNum = 1");
    $db->query("UPDATE Item SET stockItemQty = 0  WHERE sparePartNum = 2");
    $db->query("UPDATE Item SET stockItemQty = 3  WHERE sparePartNum = 3");
    $db->query("UPDATE Item SET stockItemQty = 0  WHERE sparePartNum = 4");
    $db->query("UPDATE Item SET stockItemQty = 3  WHERE sparePartNum = 5");
    $db->query("UPDATE Item SET stockItemQty = 0  WHERE sparePartNum = 6");
    $db->query("UPDATE Item SET stockItemQty = 3  WHERE sparePartNum = 7");
    $db->query("UPDATE Item SET stockItemQty = 0  WHERE sparePartNum = 8");
    $db->query("UPDATE Item SET stockItemQty = 10 WHERE sparePartNum = 9");
    $db->query("UPDATE Item SET stockItemQty = 10 WHERE sparePartNum = 10");
    $db->query("UPDATE Item SET stockItemQty = 10 WHERE sparePartNum = 11");
    $db->query("UPDATE Item SET price = 5000 WHERE sparePartNum = 1");
    $db->query("UPDATE Item SET price = 500 WHERE sparePartNum = 5");
}

// Helper function to create test user token
function createTestToken(string $userId, string $role = 'Dealer'): string
{
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $db = Database::getInstance();
    $db->query(
        "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
        [$userId, $token, $expiresAt]
    );

    return $token;
}

// Helper function to create test order
function createTestOrder(string $dealerId, array $items = []): string
{
    $order = new Order();

    if (empty($items)) {
        $items = [
            [
                'itemID' => '1',
                'quantity' => 1,
                'price' => 5000,
                'weight' => 70
            ]
        ];
    }

    $orderData = ['cart' => $items];
    $user = [
        'id' => $dealerId,
        'UserID' => $dealerId,
        'deliveryAddress' => 'Test Address, Test City'
    ];

    return $order->placeOrder($orderData, $user);
}

echo "Test environment initialized\n";
