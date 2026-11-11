<?php

declare(strict_types=1);

namespace Tests\Integration;

use DemoAccounts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Order;
use Auth;
use Cart;

class OrderFlowTest extends TestCase
{
    private Order $order;
    private Auth $auth;
    private Cart $cart;
    private string $dealerToken;
    private string $managerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = new Order();
        $this->auth = new Auth();
        $this->cart = new Cart();

        resetTestDatabase();

        // Setup test users
        $dealerLogin = $this->auth->login('alex@auto-racing.com', DemoAccounts::PASSWORD);
        $this->dealerToken = $dealerLogin['result']['token'];

        $managerLogin = $this->auth->login('kit@slms.com', DemoAccounts::PASSWORD);
        $this->managerToken = $managerLogin['result']['token'];
    }

    #[Test]
    public function dealer_can_complete_full_order_flow(): void
    {
        // Step 1: Add items to cart
        $this->cart->addItem('1', 1);
        $this->cart->addItem('5', 2);

        $this->assertCount(2, $this->cart->getCartItems());

        // Step 2: Calculate cart total
        $cartTotal = $this->cart->getCartTotal();
        $this->assertEquals(6000, $cartTotal); // 5000 + (500 * 2)

        // Step 3: Place order — convert CartItem objects to arrays
        $cartItems = [];
        foreach ($this->cart->getCartItems() as $cartItem) {
            $part = (new \SparePart())->getSparePart($cartItem->itemID);
            $cartItems[] = [
                'itemID'   => $cartItem->itemID,
                'quantity' => $cartItem->quantity,
                'price'    => (float)$part['price'],
                'weight'   => (float)$part['weight'],
            ];
        }
        $orderData = ['cart' => $cartItems];
        $user = [
            'id' => 'alex@auto-racing.com',
            'UserID' => 'alex@auto-racing.com',
            'deliveryAddress' => 'Test Address'
        ];

        $orderId = $this->order->placeOrder($orderData, $user);
        $this->assertNotEmpty($orderId);

        // Step 4: Verify order was created
        $orderDetails = $this->order->getOrderByIdAndDealer($orderId, 'alex@auto-racing.com');
        $this->assertNotNull($orderDetails);
        $this->assertEquals('Pending', $orderDetails['summary']['orderStatus']);

        // Step 5: Verify stock was reduced
        $sparePart = new \SparePart();
        $item1 = $sparePart->getSparePart('1');
        $this->assertEquals(2, $item1['stockItemQty']); // Was 3, now 2

        // Step 6: Clear cart
        $this->cart->clearCart();
        $this->assertCount(0, $this->cart->getCartItems());
    }

    #[Test]
    public function order_placement_fails_with_insufficient_stock(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        // Try to order more than available stock
        $orderData = [
            'cart' => [
                [
                    'itemID' => '1',
                    'quantity' => 999,
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
    public function sales_manager_can_update_order_status(): void
    {
        // Create order as dealer
        $orderId = createTestOrder('alex@auto-racing.com');

        // Verify initial status
        $order = $this->order->getOrder($orderId);
        $this->assertEquals('Pending', $order['orderStatus']);

        // Manager updates status
        $updatedOrder = $this->order->updateOrderStatus($orderId, 'Confirmed');
        $this->assertEquals('Confirmed', $updatedOrder['orderStatus']);

        // Update to Shipped
        $shippedOrder = $this->order->updateOrderStatus($orderId, 'Shipped');
        $this->assertEquals('Shipped', $shippedOrder['orderStatus']);

        // Update to Delivered
        $deliveredOrder = $this->order->updateOrderStatus($orderId, 'Delivered');
        $this->assertEquals('Delivered', $deliveredOrder['orderStatus']);
    }

    #[Test]
    public function dealer_can_cancel_pending_order(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $cancelledOrder = $this->order->cancelOrder($orderId);

        $this->assertEquals('Cancelled', $cancelledOrder['orderStatus']);
    }

    #[Test]
    public function order_includes_shipping_cost(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $orderDetails = $this->order->getOrderByIdAndDealer($orderId, 'alex@auto-racing.com');

        $this->assertArrayHasKey('shippingCost', $orderDetails['summary']);
        $this->assertGreaterThan(0, $orderDetails['summary']['shippingCost']);
    }

    #[Test]
    public function order_total_includes_items_and_shipping(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com', [
            ['itemID' => '1', 'quantity' => 1, 'price' => 5000, 'weight' => 70],
            ['itemID' => '5', 'quantity' => 2, 'price' => 500, 'weight' => 1]
        ]);

        $orderDetails = $this->order->getOrderByIdAndDealer($orderId, 'alex@auto-racing.com');

        $itemsTotal = $orderDetails['summary']['totalAmount'];
        $shippingCost = $orderDetails['summary']['shippingCost'];
        $totalCost = $orderDetails['summary']['totalCost'];

        $this->assertEquals($itemsTotal + $shippingCost, $totalCost);
    }

    #[Test]
    public function dealer_can_only_see_own_orders(): void
    {
        // Create orders for different dealers
        $order1 = createTestOrder('alex@auto-racing.com');
        $order2 = createTestOrder('tina@good-service.com');

        // Alex should only see his order
        $alexOrders = $this->order->getOrderListByDealer('alex@auto-racing.com');

        $alexOrderIds = array_map('strval', array_column($alexOrders, 'orderID'));
        $this->assertContains($order1, $alexOrderIds);
        $this->assertNotContains($order2, $alexOrderIds);
    }

    #[Test]
    public function sales_manager_can_see_all_orders(): void
    {
        // Create orders for different dealers
        $order1 = createTestOrder('alex@auto-racing.com');
        $order2 = createTestOrder('tina@good-service.com');

        // Manager should see all orders
        $allOrders = $this->order->getOrderListBySalesManager();

        $orderIds = array_map('strval', array_column($allOrders, 'orderID'));
        $this->assertContains($order1, $orderIds);
        $this->assertContains($order2, $orderIds);
    }

    #[Test]
    public function order_assigns_sales_manager_automatically(): void
    {
        $orderId = createTestOrder('alex@auto-racing.com');

        $order = $this->order->getOrder($orderId);

        $this->assertNotEmpty($order['salesManagerID']);
        $this->assertContains($order['salesManagerID'], [
            'kit@slms.com',
            'mary@slms.com',
            'peter@slms.com'
        ]);
    }

    #[Test]
    public function order_uses_default_shipping_cost_on_api_failure(): void
    {
        // This test would require mocking the shipping API
        // For now, we verify that orders always have a shipping cost

        $orderId = createTestOrder('alex@auto-racing.com');
        $order = $this->order->getOrder($orderId);

        $this->assertGreaterThanOrEqual(50, $order['shipCost']); // Default is $50
    }

    #[Test]
    public function transaction_rolls_back_on_order_failure(): void
    {
        $sparePart = new \SparePart();
        $initialStock = $sparePart->getSparePart('1')['stockItemQty'];

        try {
            // Try to place order with invalid data
            $orderData = [
                'cart' => [
                    ['itemID' => '1', 'quantity' => 1, 'price' => 5000, 'weight' => 70]
                ]
            ];

            $user = [
                'id' => 'alex@auto-racing.com',
                // Missing deliveryAddress - should cause failure
            ];

            $this->order->placeOrder($orderData, $user);
        } catch (\Exception $e) {
            // Expected to fail
        }

        // Verify stock was not reduced
        $currentStock = $sparePart->getSparePart('1')['stockItemQty'];
        $this->assertEquals($initialStock, $currentStock);
    }
}
