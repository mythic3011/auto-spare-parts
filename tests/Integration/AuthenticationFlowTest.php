<?php

declare(strict_types=1);

namespace Tests\Integration;

use DemoAccounts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Auth;
use User;

class AuthenticationFlowTest extends TestCase
{
    private Auth $auth;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = new Auth();
        $this->user = new User();
        resetTestDatabase();
    }

    #[Test]
    public function dealer_can_complete_full_login_flow(): void
    {
        // Step 1: Login
        $result = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);

        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('token', $result['result']);

        $token = $result['result']['token'];

        // Step 2: Validate token
        $userData = $this->auth->validateToken($token);

        $this->assertNotNull($userData);
        $this->assertEquals('alex@auto-racing.com', $userData['UserID']);
        $this->assertEquals('Dealer', $userData['UserRole']);

        // Step 3: Logout
        $logoutResult = $this->auth->logout($token);
        $this->assertArrayHasKey('message', $logoutResult);

        // Step 4: Token should be invalid after logout
        $invalidated = $this->auth->validateToken($token);
        $this->assertNull($invalidated);
    }

    #[Test]
    public function manager_can_complete_full_login_flow(): void
    {
        $result = $this->auth->login('kit@slms.com', DemoAccounts::PASSWORD);

        $this->assertArrayHasKey('result', $result);
        $this->assertEquals('SalesManager', $result['result']['role']);

        $token = $result['result']['token'];
        $userData = $this->auth->validateToken($token);

        $this->assertNotNull($userData);
        $this->assertEquals('SalesManager', $userData['UserRole']);
    }

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $result = $this->auth->login('alex@auto-racing.com', 'wrongpassword');

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('result', $result);
    }

    #[Test]
    public function login_fails_with_unknown_user(): void
    {
        $result = $this->auth->login('ghost@example.com', DemoAccounts::PASSWORD);

        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function token_is_unique_per_login(): void
    {
        $result1 = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);
        $result2 = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);

        $this->assertNotEquals(
            $result1['result']['token'],
            $result2['result']['token']
        );
    }

    #[Test]
    public function expired_token_is_rejected(): void
    {
        $db = \Database::getInstance();

        // Insert an already-expired token
        $expiredToken = bin2hex(random_bytes(32));
        $db->query(
            "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            ['alex@auto-racing.com', $expiredToken, date('Y-m-d H:i:s', time() - 3600)]
        );

        $userData = $this->auth->validateToken($expiredToken);

        $this->assertNull($userData);
    }

    #[Test]
    public function bearer_prefix_is_stripped_from_token(): void
    {
        $loginResult = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);
        $token = $loginResult['result']['token'];

        // Validate with Bearer prefix
        $userData = $this->auth->validateToken("Bearer {$token}");

        $this->assertNotNull($userData);
        $this->assertEquals('alex@auto-racing.com', $userData['UserID']);
    }

    #[Test]
    public function dealer_is_redirected_to_dealer_page(): void
    {
        $result = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);

        $page = $result['result']['page'];

        $this->assertStringContainsString('dealer', strtolower($page));
    }

    #[Test]
    public function manager_is_redirected_to_staff_page(): void
    {
        $result = $this->auth->login('kit@slms.com', DemoAccounts::PASSWORD);

        $page = $result['result']['page'];

        $this->assertStringContainsString('staff', strtolower($page));
    }

    #[Test]
    public function multiple_users_can_be_logged_in_simultaneously(): void
    {
        $dealer = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);
        $manager = $this->auth->login('kit@slms.com', DemoAccounts::PASSWORD);

        $dealerToken = $dealer['result']['token'];
        $managerToken = $manager['result']['token'];

        // Both tokens should be valid
        $dealerData = $this->auth->validateToken($dealerToken);
        $managerData = $this->auth->validateToken($managerToken);

        $this->assertNotNull($dealerData);
        $this->assertNotNull($managerData);

        $this->assertEquals('Dealer', $dealerData['UserRole']);
        $this->assertEquals('SalesManager', $managerData['UserRole']);
    }
}
