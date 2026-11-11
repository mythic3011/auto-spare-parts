<?php

declare(strict_types=1);

/**
 * Driver-neutral database operations. Callers depend on this, not on mysqli or PDO.
 */
interface DatabaseAdapter
{
    /**
     * @param array<mixed> $params
     * @return array<array<mixed>>|int
     */
    public function query(string $query, array $params = []): array|int;

    /**
     * @param array<mixed> $params
     * @return array<array<mixed>>
     */
    public function fetchAll(string $query, array $params = []): array;

    /**
     * @param array<mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $query, array $params = []): ?array;

    /**
     * @param array<mixed> $params
     */
    public function execute(string $query, array $params = []): int;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;

    public function lastInsertId(): int|string;

    public function close(): void;
}
