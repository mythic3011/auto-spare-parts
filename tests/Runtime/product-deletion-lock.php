<?php

declare(strict_types=1);

if (getenv('STAFF_TEST_PROJECT') !== 'asp-staff-review') {
    throw new RuntimeException('Requires the disposable asp-staff-review stack');
}
require_once __DIR__ . '/../../resources/php/class/SparePart.php';
require_once __DIR__ . '/../../resources/php/class/Order.php';
$config = require __DIR__ . '/../../resources/php/class/config.php';
$config['db_query_cache'] = false;
$db = DatabaseAdapterFactory::create($config);

final class PausedShipping extends Shipping
{
    public function __construct(private string $signal) { parent::__construct(); }
    public function calculateShippingCost(string $mode, int|float $value, ?float $fallbackCost = null): float
    {
        file_put_contents($this->signal . '.locked', 'locked');
        $deadline = microtime(true) + 10;
        while (!file_exists($this->signal . '.continue')) {
            if (microtime(true) > $deadline) throw new RuntimeException('Checkout synchronization timed out');
            usleep(20000);
        }
        return parent::calculateShippingCost($mode, $value, $fallbackCost);
    }
}

if (($argv[1] ?? '') === 'checkout') {
    (new Order($db, new PausedShipping($argv[2])))->placeOrder(
        ['cart' => [['itemID' => '7', 'quantity' => 1]]],
        ['id' => 'alex@auto-racing.com', 'deliveryAddress' => 'Disposable lock-test address']
    );
    echo "checkout-committed\n";
    exit;
}
if (($argv[1] ?? '') === 'delete') {
    echo "delete-started\n";
    flush();
    try {
        (new SparePart($db))->deleteSparePart(7);
        throw new RuntimeException('Deletion unexpectedly succeeded');
    } catch (ProductInOrderException $exception) {
        echo "deletion-blocked\n";
        exit;
    }
}

function ensure(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function worker(array $args, &$pipes)
{
    return proc_open([PHP_BINARY, __FILE__, ...$args], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
}

$signal = tempnam(sys_get_temp_dir(), 'product-lock-');
$db->execute('UPDATE Item SET stockItemQty = 5 WHERE sparePartNum = 7');
$checkout = worker(['checkout', $signal], $checkoutPipes);
$delete = null;
try {
    $deadline = microtime(true) + 10;
    while (!file_exists($signal . '.locked')) {
        ensure(microtime(true) < $deadline, 'Checkout never acquired the product lock');
        usleep(20000);
    }
    $delete = worker(['delete'], $deletePipes);
    ensure(trim((string) fgets($deletePipes[1])) === 'delete-started', 'Deletion worker did not start');
    usleep(150000);
    ensure(proc_get_status($delete)['running'], 'Deletion did not wait for the checkout lock');
    file_put_contents($signal . '.continue', 'continue');
    $checkoutOutput = stream_get_contents($checkoutPipes[1]);
    ensure(proc_close($checkout) === 0 && str_contains($checkoutOutput, 'checkout-committed'), 'Real checkout failed');
    $checkout = null;
    $deleteOutput = stream_get_contents($deletePipes[1]);
    ensure(proc_close($delete) === 0 && str_contains($deleteOutput, 'deletion-blocked'), 'Deletion did not reject the newly ordered product');
    $delete = null;
    ensure($db->fetchOne('SELECT sparePartNum FROM Item WHERE sparePartNum = 7') !== null, 'Ordered product was removed');
    ensure($db->fetchOne('SELECT sparePartNum FROM OrdersItem WHERE sparePartNum = 7') !== null, 'Order line was lost');
    echo "PASS: real checkout holds the catalog lock; concurrent deletion waits and then rejects the committed order reference.\n";
} finally {
    file_put_contents($signal . '.continue', 'continue');
    if (is_resource($checkout)) { proc_terminate($checkout); proc_close($checkout); }
    if (is_resource($delete)) { proc_terminate($delete); proc_close($delete); }
    foreach ([$signal, $signal . '.locked', $signal . '.continue'] as $file) if (is_file($file)) unlink($file);
}
