<?php

declare(strict_types=1);

/**
 * Request-scoped cache for identical reads. Any write clears it.
 */
final class CachingDatabaseAdapter extends AbstractDatabaseAdapter
{
    /** @var array<string, array<array<mixed>>> */
    private array $rows = [];

    public function __construct(
        private readonly DatabaseAdapter $inner,
        private readonly int $maxEntries = 64
    ) {
    }

    public function query(string $query, array $params = []): array|int
    {
        if (!$this->isRead($query)) {
            $this->clear();

            return $this->inner->query($query, $params);
        }

        $key = $query . "\0" . serialize($params);
        if (isset($this->rows[$key])) {
            return $this->rows[$key];
        }

        $result = $this->inner->query($query, $params);
        if (is_array($result) && count($this->rows) < $this->maxEntries) {
            $this->rows[$key] = $result;
        }

        return $result;
    }

    public function beginTransaction(): void
    {
        $this->clear();
        $this->inner->beginTransaction();
    }

    public function commit(): void
    {
        $this->inner->commit();
        $this->clear();
    }

    public function rollback(): void
    {
        $this->inner->rollback();
        $this->clear();
    }

    public function lastInsertId(): int|string
    {
        return $this->inner->lastInsertId();
    }

    public function close(): void
    {
        $this->clear();
        $this->inner->close();
    }

    public function clear(): void
    {
        $this->rows = [];
    }

    private function isRead(string $query): bool
    {
        return (bool) preg_match('/^\s*(select|show|explain)\b/i', $query);
    }
}
