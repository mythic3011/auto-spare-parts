<?php

declare(strict_types=1);

final class DatabaseAdapterFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config): DatabaseAdapter
    {
        $driver = DatabaseConnection::driver($config);
        $adapter = match ($driver) {
            'mysqli' => new MysqliDatabaseAdapter($config),
            'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite' => new PdoDatabaseAdapter($config),
            default => throw new InvalidArgumentException('Unsupported database driver: ' . $driver),
        };

        if (!self::cacheEnabled($config)) {
            return $adapter;
        }

        $size = (int) ($config['db_query_cache_size'] ?? 64);

        return new CachingDatabaseAdapter($adapter, max(0, $size));
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function cacheEnabled(array $config): bool
    {
        if (array_key_exists('db_query_cache', $config)) {
            return filter_var($config['db_query_cache'], FILTER_VALIDATE_BOOLEAN);
        }

        return ($config['app_env'] ?? 'production') !== 'testing';
    }
}
