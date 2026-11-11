<?php

declare(strict_types=1);

// Run only in docker/compose.validation.yml: every database mutation is disposable.
if (getenv('VALIDATION_STACK') !== 'true') {
    throw new RuntimeException('This script requires the isolated validation stack');
}
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../resources/php/class/Order.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function http(string $path, string $method = 'GET', ?array $payload = null, ?string $token = null): array
{
    $ch = curl_init('http://nginx' . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json', $token !== null ? 'Authorization: Bearer ' . $token : null]), CURLOPT_TIMEOUT => 10]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    }
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, $body];
}

foreach (['/.env', '/.git/config', '/setup.sql', '/sql-scripts/setup.sql', '/composer.json',
    '/apps/api/config/doctrine.php', '/resources/php/bootstrap.php', '/resources/php/class/config.php',
    '/resources/py/api.py', '/resources/js/.env', '/resources/image/leak.sql'] as $path) {
    [$status] = http($path);
    check(in_array($status, [403, 404], true), 'Private file exposed: ' . $path);
}
check(http('/resources/css/index.css')[0] === 200, 'Public assets unavailable');
check(http('/')[0] === 200, 'Legacy index unavailable');
[$status, $body] = http('/api/v1/auth/login', 'POST', []);
check($status === 400 && json_decode($body, true)['error'] === 'Invalid request payload.', 'v1 login routing failed');
check(http('/api/v1/auth/me')[0] === 401, 'v1 me routing failed');
check(http('/api/not-a-route')[0] === 404, 'Legacy gateway unavailable');
check(http('/api/orders/dealer-list')[0] === 401, 'Legacy order dispatch unavailable');
check(http('/api/v1/not-a-route')[0] === 404, 'v1 unknown route failed');

// Create the actual identity schema and seed one synthetic user for an HTTP login.
$em = require __DIR__ . '/../../apps/api/bootstrap/doctrine.php';
$tool = new Doctrine\ORM\Tools\SchemaTool($em);
if (!$em->getConnection()->createSchemaManager()->tablesExist(['roles', 'users'])) {
    $tool->createSchema($em->getMetadataFactory()->getAllMetadata());
}
$em->getConnection()->executeStatement('DELETE FROM auth_tokens');
$em->getConnection()->executeStatement('DELETE FROM users');
$em->getConnection()->executeStatement('DELETE FROM roles');
$role = new App\Api\Domain\Identity\Entity\Role('dealer', 'Dealer');
$user = new App\Api\Domain\Identity\Entity\User('validation-user', 'validation@example.test', password_hash('validation-only', PASSWORD_DEFAULT), $role);
$em->persist($role);
$em->persist($user);
$em->flush();
[$status, $body] = http('/api/v1/auth/login', 'POST', ['email' => 'validation@example.test', 'password' => 'validation-only']);
$data = json_decode($body, true);
check($status === 200 && ($data['data']['user_public_id'] ?? null) === 'validation-user', 'Full v1 login failed');
check(is_string($data['data']['token'] ?? null) && $data['data']['token'] !== '', 'Login token missing');
$token = $data['data']['token'];
check(http('/api/v1/auth/me', 'GET', null, $token)[0] === 200, 'Bearer forwarding failed');
check(http('/api/v1/auth/logout', 'POST', null, $token)[0] === 200, 'Logout routing failed');
check(http('/api/v1/auth/me', 'GET', null, $token)[0] === 401, 'Revoked token still accepted');


$db = Database::getInstance();
$db->execute('UPDATE Item SET stockItemQty = 10 WHERE sparePartNum = 5');
$catalog = $db->fetchOne('SELECT price FROM Item WHERE sparePartNum = 5');
$buyer = ['id' => 'alex@auto-racing.com', 'deliveryAddress' => 'Synthetic validation address'];
$order = new Order();
$id = $order->placeOrder(['cart' => [['itemID' => '5', 'quantity' => 1, 'price' => 0, 'weight' => 0]]], $buyer);
$line = $db->fetchOne('SELECT sparePartOrderPrice FROM OrdersItem WHERE orderID = ?', [$id]);
check((string) $line['sparePartOrderPrice'] === (string) $catalog['price'], 'Submitted price persisted');
check((int) $db->fetchOne('SELECT shipCost FROM Orders WHERE orderID = ?', [$id])['shipCost'] === 300, 'Real shipping quote failed');
check(strlen($order->getOrder($id)['publicID']) === 32, 'Public order identifier missing');

// Independent processes exercise simultaneous checkouts on distinct DB connections.
$children = [];
for ($i = 0; $i < 4; $i++) {
    $pid = pcntl_fork();
    if ($pid === 0) {
        // A fork must never reuse the parent's database socket.
        $config = require __DIR__ . '/../../resources/php/class/config.php';
        $adapter = DatabaseAdapterFactory::create($config);
        (new Order($adapter))->placeOrder(['cart' => [['itemID' => '5', 'quantity' => 1]]], $buyer);
        exit(0);
    }
    check($pid > 0, 'Could not start checkout process');
    $children[] = $pid;
}
foreach ($children as $pid) {
    pcntl_waitpid($pid, $status);
    check(pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0, 'Concurrent checkout failed');
}
// Forked destructors close inherited sockets, so use a fresh connection here too.
$config = require __DIR__ . '/../../resources/php/class/config.php';
$db = DatabaseAdapterFactory::create($config);
$counts = $db->fetchOne('SELECT COUNT(*) AS total, COUNT(DISTINCT orderID) AS ids, COUNT(DISTINCT publicID) AS publicIds FROM Orders');
check($counts['total'] === $counts['ids'] && $counts['total'] === $counts['publicIds'], 'Order ID collision');
check((int) $db->fetchOne('SELECT stockItemQty FROM Item WHERE sparePartNum = 5')['stockItemQty'] === 5, 'Stock reservation incorrect');

$before = (int) $counts['total'];
putenv('SHIPPING_SERVICE_URL=http://api:1');
try {
    (new Order($db))->placeOrder(['cart' => [['itemID' => '5', 'quantity' => 1]]], $buyer);
    throw new LogicException('Unavailable shipping incorrectly accepted');
} catch (RuntimeException $e) {
    check($e->getMessage() === 'Shipping cost service is unavailable', 'Unexpected failure');
}
check((int) $db->fetchOne('SELECT COUNT(*) AS n FROM Orders')['n'] === $before, 'Failed checkout created an order');
check((int) $db->fetchOne('SELECT stockItemQty FROM Item WHERE sparePartNum = 5')['stockItemQty'] === 5, 'Failed checkout changed stock');
echo "Gateway, real login, authoritative checkout, concurrent IDs, and shipping rollback passed.\n";
