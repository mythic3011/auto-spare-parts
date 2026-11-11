<?php

declare(strict_types=1);

abstract class AbstractDatabaseAdapter implements DatabaseAdapter
{
    public function fetchAll(string $query, array $params = []): array
    {
        $result = $this->query($query, $params);

        return is_array($result) ? $result : [];
    }

    public function fetchOne(string $query, array $params = []): ?array
    {
        $row = $this->fetchAll($query, $params)[0] ?? null;

        return is_array($row) ? $row : null;
    }

    public function execute(string $query, array $params = []): int
    {
        $result = $this->query($query, $params);

        return is_int($result) ? $result : 0;
    }

    protected function fail(Exception $exception): never
    {
        error_log('Database error: ' . $exception->getMessage());
        throw $exception;
    }
}
