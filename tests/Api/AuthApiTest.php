<?php

declare(strict_types=1);

namespace Tests\Api;

use DemoAccounts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthApiTest extends TestCase
{
    private string $baseUrl = 'http://nginx/resources/php/login.php';

    protected function setUp(): void
    {
        parent::setUp();
        resetTestDatabase();
    }

    protected function tearDown(): void
    {
        resetTestDatabase();
        parent::tearDown();
    }

    #[Test]
    public function login_with_valid_dealer_credentials_returns_token(): void
    {
        $response = $this->post([
            'username' => 'alex@auto-racing.com',
            'password' => DemoAccounts::PASSWORD,
        ]);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('result', $response['body']);
        $this->assertArrayHasKey('token', $response['body']['result']['result']);
        $this->assertEquals('Dealer', $response['body']['result']['result']['role']);
    }

    #[Test]
    public function login_with_valid_manager_credentials_returns_token(): void
    {
        $response = $this->post([
            'username' => 'kit@slms.com',
            'password' => DemoAccounts::PASSWORD,
        ]);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('result', $response['body']);
        $this->assertEquals('SalesManager', $response['body']['result']['result']['role']);
    }

    #[Test]
    public function login_with_wrong_password_returns_error(): void
    {
        $response = $this->post([
            'username' => 'alex@auto-racing.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertNotEquals(200, $response['status_code']);
    }

    #[Test]
    public function login_with_unknown_user_returns_error(): void
    {
        $response = $this->post([
            'username' => 'ghost@example.com',
            'password' => DemoAccounts::PASSWORD,
        ]);

        $this->assertNotEquals(200, $response['status_code']);
    }

    #[Test]
    public function login_without_credentials_returns_error(): void
    {
        $response = $this->post([]);

        $this->assertNotEquals(200, $response['status_code']);
    }

    private function post(array $data): array
    {
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Test-Env: testing',
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $statusCode,
            'body'        => json_decode($response, true) ?? [],
        ];
    }
}
