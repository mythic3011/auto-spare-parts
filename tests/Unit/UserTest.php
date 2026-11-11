<?php

declare(strict_types=1);

namespace Tests\Unit;

use DemoAccounts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use User;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
        resetTestDatabase();
    }

    #[Test]
    public function it_retrieves_existing_user(): void
    {
        $user = $this->user->getUser('alex@auto-racing.com');

        $this->assertNotNull($user);
        $this->assertEquals('alex@auto-racing.com', $user['UserID']);
        $this->assertEquals('Dealer', $user['UserRole']);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_user(): void
    {
        $user = $this->user->getUser('nobody@example.com');

        $this->assertNull($user);
    }

    #[Test]
    public function it_gets_correct_role_for_dealer(): void
    {
        $role = $this->user->getRoleByUserId('alex@auto-racing.com');

        $this->assertEquals('Dealer', $role);
    }

    #[Test]
    public function it_gets_correct_role_for_manager(): void
    {
        $role = $this->user->getRoleByUserId('kit@slms.com');

        $this->assertEquals('SalesManager', $role);
    }

    #[Test]
    public function it_returns_null_role_for_unknown_user(): void
    {
        $role = $this->user->getRoleByUserId('nobody@example.com');

        $this->assertNull($role);
    }

    #[Test]
    public function it_returns_correct_page_for_dealer_role(): void
    {
        $page = $this->user->getPageForRole('Dealer');

        $this->assertStringContainsString('dealer', strtolower($page));
    }

    #[Test]
    public function it_returns_correct_page_for_manager_role(): void
    {
        $page = $this->user->getPageForRole('SalesManager');

        $this->assertStringContainsString('staff', strtolower($page));
    }

    #[Test]
    public function it_returns_default_page_for_unknown_role(): void
    {
        $page = $this->user->getPageForRole('UnknownRole');

        $this->assertEquals('index.php', $page);
    }

    #[Test]
    public function it_verifies_correct_password(): void
    {
        $hash = password_hash(DemoAccounts::PASSWORD, PASSWORD_BCRYPT);

        $this->assertTrue($this->user->verifyPassword(DemoAccounts::PASSWORD, $hash));
    }

    #[Test]
    public function it_rejects_incorrect_password(): void
    {
        $hash = password_hash(DemoAccounts::PASSWORD, PASSWORD_BCRYPT);

        $this->assertFalse($this->user->verifyPassword('wrongpassword', $hash));
    }

    #[Test]
    public function it_logs_login_attempt(): void
    {
        $db = \Database::getInstance();

        // Count before
        $before = $db->query("SELECT COUNT(*) as cnt FROM login_attempts WHERE username = ?", ['alex@auto-racing.com']);
        $countBefore = (int)$before[0]['cnt'];

        $this->user->logLoginAttempt('alex@auto-racing.com', true);

        // Count after
        $after = $db->query("SELECT COUNT(*) as cnt FROM login_attempts WHERE username = ?", ['alex@auto-racing.com']);
        $countAfter = (int)$after[0]['cnt'];

        $this->assertEquals($countBefore + 1, $countAfter);
    }

    #[Test]
    public function it_detects_locked_user_after_max_attempts(): void
    {
        $db = \Database::getInstance();
        $username = 'alex@auto-racing.com';

        // Insert 5 failed attempts within lockout window
        for ($i = 0; $i < 5; $i++) {
            $db->query(
                "INSERT INTO login_attempts (username, ip_address, attempt_time, success) VALUES (?, '127.0.0.1', NOW(), 0)",
                [$username]
            );
        }

        $this->assertTrue($this->user->isUserLocked($username));
    }

    #[Test]
    public function it_does_not_lock_user_with_few_attempts(): void
    {
        $db = \Database::getInstance();
        $username = 'alex@auto-racing.com';

        // Insert only 2 failed attempts
        for ($i = 0; $i < 2; $i++) {
            $db->query(
                "INSERT INTO login_attempts (username, ip_address, attempt_time, success) VALUES (?, '127.0.0.1', NOW(), 0)",
                [$username]
            );
        }

        $this->assertFalse($this->user->isUserLocked($username));
    }
}
