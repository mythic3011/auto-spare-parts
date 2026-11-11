<?php

declare(strict_types=1);

final class MysqliDatabaseAdapter extends AbstractDatabaseAdapter
{
    private ?mysqli $conn = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        if (DatabaseConnection::driver($config) !== 'mysqli') {
            throw new InvalidArgumentException('Mysqli adapter cannot use driver ' . DatabaseConnection::driver($config));
        }

        $this->conn = new mysqli(
            DatabaseConnection::host($config),
            (string) $config['db_user'],
            (string) ($config['db_pass'] ?? ''),
            DatabaseConnection::name($config),
            DatabaseConnection::port($config)
        );

        if ($this->conn->connect_error) {
            throw new Exception('Connection failed: ' . $this->conn->connect_error);
        }

        if (!$this->conn->set_charset(DatabaseConnection::charset($config))) {
            throw new Exception('Failed to set database charset');
        }
    }

    public function query(string $query, array $params = []): array|int
    {
        try {
            $statement = $this->connection()->prepare($query);
            if ($statement === false) {
                throw new Exception('Failed to prepare statement: ' . $this->connection()->error);
            }

            if ($params !== []) {
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                }
                $statement->bind_param($types, ...$params);
            }

            if (!$statement->execute()) {
                throw new Exception('Failed to execute statement: ' . $statement->error);
            }

            $result = $statement->get_result();
            if ($result === false && $statement->errno) {
                throw new Exception('Failed to get result: ' . $statement->error);
            }

            if ($result === false) {
                return $statement->affected_rows;
            }

            return $result->fetch_all(MYSQLI_ASSOC) ?: [];
        } catch (Exception $exception) {
            $this->fail($exception);
        }
    }

    public function beginTransaction(): void
    {
        $this->connection()->begin_transaction();
    }

    public function commit(): void
    {
        $this->connection()->commit();
    }

    public function rollback(): void
    {
        $this->connection()->rollback();
    }

    public function lastInsertId(): int|string
    {
        return (int) $this->connection()->insert_id;
    }

    public function close(): void
    {
        if ($this->conn instanceof mysqli) {
            $this->conn->close();
            $this->conn = null;
        }
    }

    private function connection(): mysqli
    {
        if (!$this->conn instanceof mysqli) {
            throw new Exception('Database connection is closed');
        }

        return $this->conn;
    }
}
