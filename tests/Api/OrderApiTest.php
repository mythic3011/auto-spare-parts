<?php

declare(strict_types=1);

namespace Tests\Api;

use DemoAccounts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderApiTest extends TestCase
{
    private string $baseUrl = 'http://nginx/resources/php/order_api.php';
    private string $dealerToken;
    private string $managerToken;

    protected function setUp(): void
    {
        parent::setUp();
        resetTestDatabase();

        // Get authentication tokens after the database reset so they remain valid.
        $this->dealerToken = $this->getAuthToken('alex@auto-racing.com', DemoAccounts::PASSWORD);
        $this->managerToken = $this->getAuthToken('kit@slms.com', DemoAccounts::PASSWORD);
    }

    protected function tearDown(): void
    {
        resetTestDatabase();
        parent::tearDown();
    }

    #[Test]
    public function get_dealer_order_list_returns_200(): void
    {
        $response = $this->get('?action=dealer-list', $this->dealerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('success', $response['body']['status']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertIsArray($response['body']['data']);
    }

    #[Test]
    public function get_manager_order_list_returns_200(): void
    {
        $response = $this->get('?action=manager-list', $this->managerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('success', $response['body']['status']);
    }

    #[Test]
    public function get_order_without_auth_returns_401(): void
    {
        $response = $this->getWithoutAuth('?action=dealer-list');

        $this->assertEquals(401, $response['status_code']);
        $this->assertEquals('error', $response['body']['status']);
    }

    #[Test]
    public function place_order_with_valid_cart_returns_201(): void
    {
        $orderData = [
            'cart' => [
                [
                    'itemID' => '1',
                    'quantity' => 1,
                    'price' => 5000,
                    'weight' => 70
                ]
            ]
        ];

        $response = $this->post('?action=place-order', $orderData, $this->dealerToken);

        $this->assertEquals(201, $response['status_code']);
        $this->assertEquals('success', $response['body']['status']);
        $this->assertArrayHasKey('id', $response['body']['data']);
    }

    #[Test]
    public function place_order_with_empty_cart_returns_400(): void
    {
        $orderData = ['cart' => []];

        $response = $this->post('?action=place-order', $orderData, $this->dealerToken);

        $this->assertEquals(400, $response['status_code']);
        $this->assertEquals('error', $response['body']['status']);
    }

    #[Test]
    public function place_order_without_cart_returns_422(): void
    {
        $orderData = [];

        $response = $this->post('?action=place-order', $orderData, $this->dealerToken);

        $this->assertEquals(422, $response['status_code']);
        $this->assertArrayHasKey('errors', $response['body']['data']);
    }

    #[Test]
    public function update_order_status_with_valid_data_returns_200(): void
    {
        // First create an order
        $orderId = createTestOrder('alex@auto-racing.com');

        $updateData = [
            'id' => $orderId,
            'status' => 'Confirmed'
        ];

        $response = $this->post('?action=update-status', $updateData, $this->managerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('success', $response['body']['status']);
        $this->assertEquals('Confirmed', $response['body']['data']['orderStatus']);
    }

    #[Test]
    public function update_order_status_with_invalid_status_returns_422(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $updateData = [
            'id' => $orderId,
            'status' => 'InvalidStatus'
        ];

        $response = $this->post('?action=update-status', $updateData, $this->managerToken);

        $this->assertEquals(422, $response['status_code']);
        $this->assertArrayHasKey('errors', $response['body']['data']);
    }

    #[Test]
    public function cancel_order_returns_200(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $cancelData = ['id' => $orderId];

        $response = $this->post('?action=cancel', $cancelData, $this->dealerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('Cancelled', $response['body']['data']['orderStatus']);
    }

    #[Test]
    public function get_order_details_for_dealer_returns_complete_data(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $response = $this->get("?action=dealer-get&id={$orderId}", $this->dealerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('summary', $response['body']['data']);
        $this->assertArrayHasKey('items', $response['body']['data']);

        $summary = $response['body']['data']['summary'];
        $this->assertArrayHasKey('id', $summary);
        $this->assertArrayHasKey('orderStatus', $summary);
        $this->assertArrayHasKey('totalCost', $summary);
        $this->assertArrayHasKey('shippingCost', $summary);
    }

    #[Test]
    public function get_order_details_for_manager_returns_complete_data(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $response = $this->get("?action=manager-get&id={$orderId}", $this->managerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('summary', $response['body']['data']);
        $this->assertArrayHasKey('items', $response['body']['data']);
    }

    #[Test]
    public function get_nonexistent_order_returns_404(): void
    {
        $response = $this->get('?action=dealer-get&id=99999', $this->dealerToken);

        $this->assertEquals(404, $response['status_code']);
        $this->assertEquals('error', $response['body']['status']);
    }

    #[Test]
    public function get_stock_report_returns_200(): void
    {
        $response = $this->get('?action=stock-report', $this->managerToken);

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('success', $response['body']['status']);
        $this->assertIsArray($response['body']['data']);
    }

    #[Test]
    public function invalid_action_returns_400(): void
    {
        $response = $this->get('?action=invalid-action', $this->dealerToken);

        $this->assertEquals(400, $response['status_code']);
        $this->assertEquals('error', $response['body']['status']);
    }

    #[Test]
    public function response_includes_timestamp(): void
    {
        $response = $this->get('?action=dealer-list', $this->dealerToken);

        $this->assertArrayHasKey('timestamp', $response['body']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $response['body']['timestamp']);
    }

    #[Test]
    public function validation_errors_include_field_details(): void
    {
        $updateData = [
            'id' => '',
            'status' => ''
        ];

        $response = $this->post('?action=update-status', $updateData, $this->managerToken);

        $this->assertEquals(422, $response['status_code']);
        $this->assertArrayHasKey('errors', $response['body']['data']);
        $this->assertArrayHasKey('id', $response['body']['data']['errors']);
        $this->assertArrayHasKey('status', $response['body']['data']['errors']);
    }

    // Helper methods

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

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $statusCode,
            'body' => json_decode($response, true)
        ];
    }

    private function getWithoutAuth(string $endpoint): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Test-Env: testing',
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $statusCode,
            'body' => json_decode($response, true)
        ];
    }

    private function post(string $endpoint, array $data, string $token): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: {$token}",
            'Content-Type: application/json',
            'X-Test-Env: testing',
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $statusCode,
            'body' => json_decode($response, true)
        ];
    }
}
