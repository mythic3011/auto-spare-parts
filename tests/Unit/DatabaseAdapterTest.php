<?php

declare(strict_types=1);

namespace Tests\Unit;

use CachingDatabaseAdapter;
use DatabaseAdapter;
use DatabaseAdapterFactory;
use DatabaseConnection;
use HttpPreflight;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DatabaseAdapterTest extends TestCase
{
    #[Test]
    public function it_rejects_a_missing_driver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DatabaseConnection::driver(['db_host' => 'localhost']);
    }

    #[Test]
    public function it_rejects_an_unknown_driver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DatabaseAdapterFactory::create([
            'db_driver' => 'oracle',
            'db_host' => 'localhost',
            'db_name' => 'projectdb',
            'db_user' => 'root',
            'db_pass' => '',
            'db_port' => 3306,
        ]);
    }

    #[Test]
    public function it_builds_a_dsn_from_the_configured_driver(): void
    {
        $mysql = [
            'db_driver' => 'pdo_mysql',
            'db_host' => 'db.internal',
            'db_port' => 3307,
            'db_name' => 'projectdb',
            'db_user' => 'app',
            'db_pass' => 'secret',
            'db_charset' => 'utf8mb4',
        ];

        $this->assertSame(
            'mysql:host=db.internal;port=3307;dbname=projectdb;charset=utf8mb4',
            DatabaseConnection::pdoDsn($mysql)
        );
        $this->assertSame('pdo_mysql', DatabaseConnection::doctrineParams($mysql)['driver']);

        $sqlite = ['db_driver' => 'pdo_sqlite', 'db_name' => ':memory:'];
        $this->assertSame('sqlite::memory:', DatabaseConnection::pdoDsn($sqlite));
        $this->assertSame(
            ['driver' => 'pdo_sqlite', 'path' => ':memory:'],
            DatabaseConnection::doctrineParams($sqlite)
        );
    }

    #[Test]
    public function it_runs_sqlite_through_the_pdo_adapter(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not available');
        }

        $db = DatabaseAdapterFactory::create([
            'db_driver' => 'pdo_sqlite',
            'db_name' => ':memory:',
            'db_query_cache' => false,
        ]);
        $db->execute('CREATE TABLE item (id INTEGER PRIMARY KEY, name TEXT)');
        $db->execute('INSERT INTO item (name) VALUES (?)', ['bolt']);

        $this->assertSame('bolt', $db->fetchOne('SELECT name FROM item WHERE id = ?', [1])['name'] ?? null);
    }

    #[Test]
    public function it_caches_reads_and_drops_them_after_a_write(): void
    {
        $inner = new class implements DatabaseAdapter {
            public int $reads = 0;

            public function query(string $query, array $params = []): array|int
            {
                if (preg_match('/^\s*select\b/i', $query)) {
                    $this->reads++;

                    return [['name' => 'bolt']];
                }

                return 1;
            }

            public function fetchAll(string $query, array $params = []): array
            {
                $result = $this->query($query, $params);

                return is_array($result) ? $result : [];
            }

            public function fetchOne(string $query, array $params = []): ?array
            {
                return $this->fetchAll($query, $params)[0] ?? null;
            }

            public function execute(string $query, array $params = []): int
            {
                $result = $this->query($query, $params);

                return is_int($result) ? $result : 0;
            }

            public function beginTransaction(): void
            {
            }

            public function commit(): void
            {
            }

            public function rollback(): void
            {
            }

            public function lastInsertId(): int|string
            {
                return 1;
            }

            public function close(): void
            {
            }
        };

        $cache = new CachingDatabaseAdapter($inner, 8);
        $cache->fetchOne('SELECT name FROM item WHERE id = ?', [1]);
        $cache->fetchOne('SELECT name FROM item WHERE id = ?', [1]);
        $this->assertSame(1, $inner->reads);

        $cache->execute('UPDATE item SET name = ?', ['nut']);
        $cache->fetchOne('SELECT name FROM item WHERE id = ?', [1]);
        $this->assertSame(2, $inner->reads);
    }

    #[Test]
    public function preflight_reflects_only_allowlisted_origins(): void
    {
        $allowed = HttpPreflight::headers('https://shop.example', ['https://shop.example'], 600);
        $this->assertSame('https://shop.example', $allowed['Access-Control-Allow-Origin']);
        $this->assertSame('600', $allowed['Access-Control-Max-Age']);

        $blocked = HttpPreflight::headers('https://evil.example', ['https://shop.example'], 600);
        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $blocked);
    }
}
