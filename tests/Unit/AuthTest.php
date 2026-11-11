<?php

declare(strict_types=1);

namespace Tests\Unit;

use DemoAccounts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Auth;

class AuthTest extends TestCase
{
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = new Auth();
        resetTestDatabase();
    }

    #[Test]
    public function it_authenticates_valid_dealer_credentials(): void
    {
        $result = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);

        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('token', $result['result']);
        $this->assertArrayHasKey('role', $result['result']);
        $this->assertEquals('Dealer', $result['result']['role']);
    }

    #[Test]
    public function it_authenticates_valid_manager_credentials(): void
    {
        $result = $this->auth->login('kit@slms.com', DemoAccounts::PASSWORD);

        $this->assertArrayHasKey('result', $result);
        $this->assertEquals('SalesManager', $result['result']['role']);
    }

    #[Test]
    public function it_rejects_invalid_username(): void
    {
        $result = $this->auth->login('nonexistent@example.com', 'password');

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid username or password', $result['error']);
    }

    #[Test]
    public function it_rejects_invalid_password(): void
    {
        $result = $this->auth->login('alex@auto-racing.com', 'wrongpassword');

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid username or password', $result['error']);
    }

    #[Test]
    public function it_generates_authentication_token(): void
    {
        $result = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);

        $token = $result['result']['token'];

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    #[Test]
    public function it_validates_valid_token(): void
    {
        $loginResult = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);
        $token = $loginResult['result']['token'];

        $userData = $this->auth->validateToken($token);

        $this->assertNotNull($userData);
        $this->assertEquals('alex@auto-racing.com', $userData['UserID']);
        $this->assertEquals('Dealer', $userData['UserRole']);
    }

    #[Test]
    public function it_rejects_invalid_token(): void
    {
        $userData = $this->auth->validateToken('invalid-token-12345');

        $this->assertNull($userData);
    }

    #[Test]
    public function it_returns_correct_page_for_dealer(): void
    {
        $result = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);

        $page = $result['result']['page'];

        $this->assertStringContainsString('dealer', strtolower($page));
    }

    #[Test]
    public function it_returns_correct_page_for_manager(): void
    {
        $result = $this->auth->login('kit@slms.com', DemoAccounts::PASSWORD);

        $page = $result['result']['page'];

        $this->assertStringContainsString('staff', strtolower($page));
    }

    #[Test]
    public function it_logs_out_successfully(): void
    {
        $loginResult = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);
        $token = $loginResult['result']['token'];

        $logoutResult = $this->auth->logout($token);

        $this->assertArrayHasKey('message', $logoutResult);

        // Token should no longer be valid
        $userData = $this->auth->validateToken($token);
        $this->assertNull($userData);
    }

    #[Test]
    public function it_authenticates_request_with_valid_token(): void
    {
        $token = createTestToken('alex@auto-racing.com', 'Dealer');

        // Simulate Authorization header
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        $user = $this->auth->authenticateRequest();

        $this->assertNotNull($user);
        $this->assertEquals('alex@auto-racing.com', $user['id']);
        $this->assertEquals('Dealer', $user['role']);
    }

    #[Test]
    public function it_returns_null_for_request_without_token(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $user = $this->auth->authenticateRequest();

        $this->assertNull($user);
    }

    #[Test]
    public function it_handles_bearer_token_format(): void
    {
        $token = createTestToken('alex@auto-racing.com', 'Dealer');

        $userData = $this->auth->validateToken("Bearer {$token}");

        $this->assertNotNull($userData);
    }
}
