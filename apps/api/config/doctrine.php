<?php

declare(strict_types=1);

$appConfig = require dirname(__DIR__, 3) . '/resources/php/class/config.php';
require_once dirname(__DIR__, 3) . '/resources/php/class/DatabaseConnection.php';

if (!is_array($appConfig) && isset($GLOBALS['APP_CONFIG']) && is_array($GLOBALS['APP_CONFIG'])) {
    $appConfig = $GLOBALS['APP_CONFIG'];
}

if (!is_array($appConfig)) {
    throw new RuntimeException('Invalid configuration format');
}

return [
    'is_dev_mode' => (bool) ($appConfig['app_debug'] ?? false),
    'proxy_dir' => __DIR__ . '/../storage/proxies',
    'cache_dir' => __DIR__ . '/../storage/cache/doctrine',
    'entity_paths' => [__DIR__ . '/../src/Domain'],
    'connection' => DatabaseConnection::doctrineParams($appConfig),
];
