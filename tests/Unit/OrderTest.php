<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Order;
use Exception;

class OrderTest extends TestCase
{
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->order = new Order();
        resetTestDatabase();
    }

    #[Test]
    public function it_places_order_with_valid_cart(): void
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

        $user = [
            'id' => 'alex@auto-racing.com',
            'UserID' => 'alex@auto-racing.com',
            'deliveryAddress' => 'G/F, ABC Building, King Yip Street, KwunTong, Kowloon, Hong Kong'
        ];

        $orderId = $this->order->placeOrder($orderData, $user);

        $this->assertNotEmpty($orderId);
        $this->assertIsString($orderId);
    }

    #[Test]
    public function it_throws_exception_for_empty_cart(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid order data');

        $orderData = ['cart' => []];
        $user = [
            'id' => 'alex@auto-racing.com',
            'deliveryAddress' => 'Test Address'
        ];

        $this->order->placeOrder($orderData, $user);
    }

    #[Test]
    public function it_throws_exception_for_insufficient_stock(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $orderData = [
            'cart' => [
                [
                    'itemID' => '1',
                    'quantity' => 999, // More than available stock
                    'price' => 5000,
                    'weight' => 70
                ]
            ]
        ];

        $user = [
            'id' => 'alex@auto-racing.com',
            'UserID' => 'alex@auto-racing.com',
            'deliveryAddress' => 'Test Address'
        ];

        $this->order->placeOrder($orderData, $user);
    }

    #[Test]
    public function it_calculates_order_total_correctly(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $total = $this->order->getOrderOrderTotal($orderId);

        $this->assertIsFloat($total);
        $this->assertGreaterThan(0, $total);
    }

    #[Test]
    public function it_retrieves_dealer_orders_only(): void
    {
        // Create orders for different dealers
        createTestOrder('alex@auto-racing.com');
        createTestOrder('tina@good-service.com');

        $orders = $this->order->getOrderListByDealer('alex@auto-racing.com');

        $this->assertIsArray($orders);

        foreach ($orders as $order) {
            $this->assertEquals('alex@auto-racing.com', $order['dealerID']);
        }
    }

    #[Test]
    public function it_retrieves_all_orders_for_sales_manager(): void
    {
        createTestOrder('alex@auto-racing.com');
        createTestOrder('tina@good-service.com');

        $orders = $this->order->getOrderListBySalesManager();

        $this->assertIsArray($orders);
        $this->assertGreaterThanOrEqual(2, count($orders));
    }

    #[Test]
    public function it_updates_order_status(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $updatedOrder = $this->order->updateOrderStatus($orderId, 'Confirmed');

        $this->assertNotNull($updatedOrder);
        $this->assertEquals('Confirmed', $updatedOrder['orderStatus']);
    }

    #[Test]
    public function it_cancels_order(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $cancelledOrder = $this->order->cancelOrder($orderId);

        $this->assertNotNull($cancelledOrder);
        $this->assertEquals('Cancelled', $cancelledOrder['orderStatus']);
    }

    #[Test]
    public function it_includes_item_count_in_order_list(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com', [
            ['itemID' => '1', 'quantity' => 1, 'price' => 5000, 'weight' => 70],
            ['itemID' => '5', 'quantity' => 2, 'price' => 500, 'weight' => 1]
        ]);

        $orders = $this->order->getOrderListByDealer('alex@auto-racing.com');

        $this->assertArrayHasKey('itemCount', $orders[0]);
        $this->assertEquals(2, $orders[0]['itemCount']);
    }

    #[Test]
    public function it_gets_order_details_with_items(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $orderDetails = $this->order->getOrderByIdAndDealer($orderId, 'alex@auto-racing.com');

        $this->assertNotNull($orderDetails);
        $this->assertArrayHasKey('summary', $orderDetails);
        $this->assertArrayHasKey('items', $orderDetails);
        $this->assertArrayHasKey('totalCost', $orderDetails['summary']);
    }

    #[Test]
    public function it_returns_null_for_unauthorized_order_access(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        // Try to access with different dealer ID
        $orderDetails = $this->order->getOrderByIdAndDealer($orderId, 'tina@good-service.com');

        $this->assertNull($orderDetails);
    }

    #[Test]
    public function it_throws_exception_for_invalid_user_id(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid user ID');

        $orderData = [
            'cart' => [
                ['itemID' => '1', 'quantity' => 1, 'price' => 5000, 'weight' => 70]
            ]
        ];

        $user = [
            'deliveryAddress' => 'Test Address'
            // Missing 'id' and 'UserID'
        ];

        $this->order->placeOrder($orderData, $user);
    }
}
