<?php

declare(strict_types=1);

namespace Tests\Unit;

use DemoAccounts;
use PHPUnit\Framework\TestCase;

final class DemoAccountsTest extends TestCase
{
    public function testFreshSeedPasswordsMatchTheDemoOverlay(): void
    {
        $sql = file_get_contents(dirname(__DIR__, 2) . '/sql-scripts/setup.sql');
        self::assertIsString($sql);
        preg_match_all('/\x27(\$2y\$[^\x27]+)\x27/', $sql, $matches);
        self::assertCount(6, $matches[1]);
        foreach ($matches[1] as $hash) {
            self::assertTrue(password_verify(DemoAccounts::PASSWORD, $hash));
        }
    }
}
