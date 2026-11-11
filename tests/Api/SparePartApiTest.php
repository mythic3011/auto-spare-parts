<?php

declare(strict_types=1);

namespace Tests\Api;

use DemoAccounts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SparePartApiTest extends TestCase
{
    private string $baseUrl = 'http://nginx/resources/php/api.php';
    private string $dealerToken;
    private string $managerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dealerToken  = $this->getAuthToken('alex@auto-racing.com', DemoAccounts::PASSWORD);
        $this->managerToken = $this->getAuthToken('kit@slms.com', DemoAccounts::PASSWORD);
    }

    protected function tearDown(): void
    {
        resetTestDatabase();
        parent::tearDown();
    }

    #[Test]
    public function get_spare_part_list_returns_all_parts(): void
    {
        $response = $this->get('?action=getSparePartList', $this->dealerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertIsArray($response['body']['result']);
        $this->assertGreaterThanOrEqual(11, count($response['body']['result']));
    }

    #[Test]
    public function get_single_spare_part_returns_correct_data(): void
    {
        $response = $this->post(['action' => 'getSparePart', 'id' => '1'], $this->dealerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('sparePartNum', $response['body']['result']);
        $this->assertEquals('1', (string)$response['body']['result']['sparePartNum']);
    }

    #[Test]
    public function get_nonexistent_spare_part_returns_null(): void
    {
        $response = $this->post(['action' => 'getSparePart', 'id' => '99999'], $this->dealerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertNull($response['body']['result']);
    }

    #[Test]
    public function manager_can_update_spare_part(): void
    {
        $response = $this->post([
            'action'       => 'updateSparePart',
            'id'           => '5',
            'stockItemQty' => 10,
            'price'        => 600.0,
        ], $this->managerToken);

        $this->assertEquals(200, $response['status_code']);
    }

    #[Test]
    public function get_spare_part_without_auth_returns_error(): void
    {
        $response = $this->getWithoutAuth('?action=getSparePartList');

        $this->assertNotEquals(200, $response['status_code']);
    }

    // Helpers

    private function getAuthToken(string $username, string $password): string
    {
        $ch = curl_init('http://nginx/resources/php/login.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'username' => $username,
            'password' => $password,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Test-Env: testing',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);
        return $decoded['result']['result']['token'];
    }

    private function get(string $endpoint, string $token): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: {$token}",
            'X-Test-Env: testing',
        ]);

        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status_code' => $statusCode, 'body' => json_decode($response, true) ?? []];
    }

    private function getWithoutAuth(string $endpoint): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Test-Env: testing',
        ]);

        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status_code' => $statusCode, 'body' => json_decode($response, true) ?? []];
    }

    private function post(array $data, string $token): array
    {
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: {$token}",
            'Content-Type: application/json',
            'X-Test-Env: testing',
        ]);

        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status_code' => $statusCode, 'body' => json_decode($response, true) ?? []];
    }
}
