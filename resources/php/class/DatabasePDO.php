<?php

declare(strict_types=1);

require_once __DIR__ . '/PdoDatabaseAdapter.php';
require_once __DIR__ . '/DatabaseConnection.php';

/**
 * PDO entry point. DB_DRIVER must be pdo_mysql, pdo_pgsql, or pdo_sqlite.
 */
class DatabasePDO extends PdoDatabaseAdapter
{
    private static ?self $instance = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $driver = DatabaseConnection::driver($config);
        if (!in_array($driver, ['pdo_mysql', 'pdo_pgsql', 'pdo_sqlite'], true)) {
            throw new InvalidArgumentException('DB_DRIVER must be a PDO driver to use DatabasePDO');
        }

        parent::__construct($config);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $config = $GLOBALS['APP_CONFIG'] ?? null;
            if (!is_array($config)) {
                $loaded = require __DIR__ . '/config.php';
                $config = is_array($loaded) ? $loaded : ($GLOBALS['APP_CONFIG'] ?? null);
            }
            if (!is_array($config)) {
                throw new Exception('Invalid configuration format');
            }

            self::$instance = new self($config);
        }

        return self::$instance;
    }
}
