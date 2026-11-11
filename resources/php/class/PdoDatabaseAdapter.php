<?php

declare(strict_types=1);

class PdoDatabaseAdapter extends AbstractDatabaseAdapter
{
    private ?PDO $pdo = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        try {
            $this->pdo = new PDO(
                DatabaseConnection::pdoDsn($config),
                (string) ($config['db_user'] ?? ''),
                (string) ($config['db_pass'] ?? ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new Exception('Connection failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public function query(string $query, array $params = []): array|int
    {
        try {
            $statement = $this->connection()->prepare($query);
            $statement->execute($params);
            if ($statement->columnCount() > 0) {
                $rows = $statement->fetchAll();

                return $rows;
            }

            return $statement->rowCount();
        } catch (Exception $exception) {
            $this->fail($exception);
        }
    }

    public function beginTransaction(): void
    {
        $pdo = $this->connection();
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        $pdo = $this->connection();
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    }

    public function rollback(): void
    {
        $pdo = $this->connection();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    public function lastInsertId(): int|string
    {
        return $this->connection()->lastInsertId();
    }

    public function close(): void
    {
        $this->pdo = null;
    }

    private function connection(): PDO
    {
        if (!$this->pdo instanceof PDO) {
            throw new Exception('Database connection is closed');
        }

        return $this->pdo;
    }
}
