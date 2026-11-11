<?php

declare(strict_types=1);

/**
 * One place that turns configuration into a driver, a PDO DSN, or Doctrine params.
 * The driver is never assumed: an empty or unknown driver is rejected.
 */
final class DatabaseConnection
{
    /**
     * @param array<string, mixed> $config
     */
    public static function driver(array $config): string
    {
        $driver = strtolower(trim((string) ($config['db_driver'] ?? '')));
        if ($driver === '') {
            throw new InvalidArgumentException('Database driver is not configured');
        }

        return $driver;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function pdoDsn(array $config): string
    {
        $driver = self::driver($config);

        return match ($driver) {
            'pdo_mysql' => sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                self::host($config),
                self::port($config),
                self::name($config),
                self::charset($config)
            ),
            'pdo_pgsql' => sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                self::host($config),
                self::port($config),
                self::name($config)
            ),
            'pdo_sqlite' => 'sqlite:' . self::name($config),
            default => throw new InvalidArgumentException('Driver ' . $driver . ' is not a PDO driver'),
        };
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function doctrineParams(array $config): array
    {
        $driver = self::driver($config);
        if (!in_array($driver, ['mysqli', 'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite'], true)) {
            throw new InvalidArgumentException('Unsupported database driver: ' . $driver);
        }

        if ($driver === 'pdo_sqlite') {
            return [
                'driver' => 'pdo_sqlite',
                'path' => self::name($config),
            ];
        }

        self::requireServer($config);

        return [
            'driver' => $driver,
            'host' => self::host($config),
            'port' => self::port($config),
            'dbname' => self::name($config),
            'user' => (string) $config['db_user'],
            'password' => (string) ($config['db_pass'] ?? ''),
            'charset' => self::charset($config),
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function charset(array $config): string
    {
        $charset = (string) ($config['db_charset'] ?? 'utf8mb4');
        if (!preg_match('/^[A-Za-z0-9_]+$/', $charset)) {
            throw new InvalidArgumentException('Invalid database charset');
        }

        return $charset;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function port(array $config): int
    {
        $port = (int) ($config['db_port'] ?? 3306);
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Invalid database port');
        }

        return $port;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function requireServer(array $config): void
    {
        foreach (['db_host', 'db_name', 'db_user'] as $key) {
            if (!isset($config[$key]) || trim((string) $config[$key]) === '') {
                throw new InvalidArgumentException('Missing required database configuration: ' . $key);
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function host(array $config): string
    {
        self::requireServer($config);
        $host = (string) $config['db_host'];
        if (strpbrk($host, "\r\n;=") !== false) {
            throw new InvalidArgumentException('Invalid database host');
        }

        return $host;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function name(array $config): string
    {
        $name = (string) ($config['db_name'] ?? '');
        if ($name === '' || strpbrk($name, "\r\n;") !== false) {
            throw new InvalidArgumentException('Invalid database name');
        }

        return $name;
    }
}
