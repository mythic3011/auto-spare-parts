<?php

declare(strict_types=1);

if (getenv('VALIDATION_STACK') !== 'true') {
    throw new RuntimeException('This script requires the isolated validation stack');
}
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../apps/api/migrations/Version20261111000100.php';

$config = require __DIR__ . '/../../apps/api/config/doctrine.php';
$admin = Doctrine\DBAL\DriverManager::getConnection($config['connection']);
$admin->executeStatement('CREATE DATABASE order_migration_validation');
$params = $config['connection'];
$params['dbname'] = 'order_migration_validation';
$conn = Doctrine\DBAL\DriverManager::getConnection($params);
try {
    $conn->executeStatement('CREATE TABLE Orders (orderID INT NOT NULL)');
    $conn->executeStatement('INSERT INTO Orders (orderID) VALUES (10), (11)');
    for ($i = 0; $i < 2; $i++) {
        $migration = new DoctrineMigrations\Version20261111000100($conn, new Psr\Log\NullLogger());
        $migration->up($conn->createSchemaManager()->introspectSchema());
        foreach ($migration->getSql() as $query) {
            $conn->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
    $conn->executeStatement('INSERT INTO Orders (publicID) VALUES (?)', [str_repeat('a', 32)]);
    if ((int) $conn->lastInsertId() !== 12 || (int) $conn->fetchOne('SELECT COUNT(DISTINCT publicID) FROM Orders') !== 3) {
        throw new RuntimeException('Order migration did not preserve and backfill existing orders');
    }
    echo "Legacy order migration, backfill, generated IDs, and repeat execution passed.\n";
} finally {
    $conn->close();
    $admin->executeStatement('DROP DATABASE order_migration_validation');
}
