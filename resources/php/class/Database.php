<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseAdapter.php';
require_once __DIR__ . '/AbstractDatabaseAdapter.php';
require_once __DIR__ . '/DatabaseConnection.php';
require_once __DIR__ . '/MysqliDatabaseAdapter.php';
require_once __DIR__ . '/PdoDatabaseAdapter.php';
require_once __DIR__ . '/CachingDatabaseAdapter.php';
require_once __DIR__ . '/DatabaseAdapterFactory.php';

/**
 * Application database entry point. The driver comes from configuration.
 */
class Database implements DatabaseAdapter
{
    private static ?self $instance = null;

    private function __construct(private readonly DatabaseAdapter $adapter)
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(DatabaseAdapterFactory::create(self::loadConfig()));
        }

        return self::$instance;
    }

    public function query(string $query, array $params = []): array|int
    {
        return $this->adapter->query($query, $params);
    }

    public function fetchAll(string $query, array $params = []): array
    {
        return $this->adapter->fetchAll($query, $params);
    }

    public function fetchOne(string $query, array $params = []): ?array
    {
        return $this->adapter->fetchOne($query, $params);
    }

    public function execute(string $query, array $params = []): int
    {
        return $this->adapter->execute($query, $params);
    }

    public function beginTransaction(): void
    {
        $this->adapter->beginTransaction();
    }

    public function commit(): void
    {
        $this->adapter->commit();
    }

    public function rollback(): void
    {
        $this->adapter->rollback();
    }

    public function lastInsertId(): int|string
    {
        return $this->adapter->lastInsertId();
    }

    public function close(): void
    {
        $this->adapter->close();
    }

    public function __destruct()
    {
        $this->close();
    }

    private function __clone(): void
    {
    }

    public function __wakeup(): void
    {
        throw new Exception('Cannot unserialize singleton');
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadConfig(): array
    {
        if (isset($GLOBALS['APP_CONFIG']) && is_array($GLOBALS['APP_CONFIG'])) {
            return $GLOBALS['APP_CONFIG'];
        }

        $configFile = __DIR__ . '/config.php';
        if (!is_file($configFile)) {
            throw new Exception('Configuration file not found');
        }

        $config = require $configFile;
        if (is_array($config)) {
            return $config;
        }

        if (isset($GLOBALS['APP_CONFIG']) && is_array($GLOBALS['APP_CONFIG'])) {
            return $GLOBALS['APP_CONFIG'];
        }

        throw new Exception('Invalid configuration format');
    }
}
