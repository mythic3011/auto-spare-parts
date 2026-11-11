<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Cart;
use Order;
use SparePart;

class CartCheckoutTest extends TestCase
{
    private Cart $cart;
    private Order $order;
    private SparePart $sparePart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cart = new Cart();
        $this->order = new Order();
        $this->sparePart = new SparePart();
        resetTestDatabase();
    }

    protected function tearDown(): void
    {
        resetTestDatabase();
        parent::tearDown();
    }

    #[Test]
    public function cart_to_order_full_flow(): void
    {
        // Step 1: Get initial stock
        $item1Before = $this->sparePart->getSparePart('1');
        $item5Before = $this->sparePart->getSparePart('5');

        $initialStock1 = (int)$item1Before['stockItemQty'];
        $initialStock5 = (int)$item5Before['stockItemQty'];

        // Step 2: Add items to cart
        $this->cart->addItem('1', 1);
        $this->cart->addItem('5', 2);

        $this->assertCount(2, $this->cart->getCartItems());

        // Step 3: Verify cart total
        $expectedTotal = (float)$item1Before['price'] * 1 + (float)$item5Before['price'] * 2;
        $this->assertEquals($expectedTotal, $this->cart->getCartTotal());

        // Step 4: Build order data from cart
        $cartItems = $this->cart->getCartItems();
        $orderCart = [];
        foreach ($cartItems as $cartItem) {
            $part = $this->sparePart->getSparePart($cartItem->itemID);
            $orderCart[] = [
                'itemID'   => $cartItem->itemID,
                'quantity' => $cartItem->quantity,
                'price'    => (float)$part['price'],
                'weight'   => (float)$part['weight'],
            ];
        }

        // Step 5: Place order
        $user = [
            'id'              => 'alex@auto-racing.com',
            'UserID'          => 'alex@auto-racing.com',
            'deliveryAddress' => 'Test Address, Test City',
        ];

        $orderId = $this->order->placeOrder(['cart' => $orderCart], $user);
        $this->assertNotEmpty($orderId);

        // Step 6: Verify stock was reduced
        $item1After = $this->sparePart->getSparePart('1');
        $item5After = $this->sparePart->getSparePart('5');

        $this->assertEquals($initialStock1 - 1, (int)$item1After['stockItemQty']);
        $this->assertEquals($initialStock5 - 2, (int)$item5After['stockItemQty']);

        // Step 7: Verify order details
        $orderDetails = $this->order->getOrderByIdAndDealer($orderId, 'alex@auto-racing.com');
        $this->assertNotNull($orderDetails);
        $this->assertCount(2, $orderDetails['items']);

        // Step 8: Clear cart
        $this->cart->clearCart();
        $this->assertCount(0, $this->cart->getCartItems());
    }

    #[Test]
    public function cart_prevents_adding_out_of_stock_item(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not enough stock');

        // Item 2 has 0 stock
        $this->cart->addItem('2', 1);
    }

    #[Test]
    public function cart_prevents_ordering_more_than_stock(): void
    {
        $this->expectException(\Exception::class);

        // Item 1 has 3 stock
        $this->cart->addItem('1', 100);
    }

    #[Test]
    public function cart_updates_quantity_correctly(): void
    {
        $this->cart->addItem('1', 1);
        $this->cart->updateQuantity('1', 2);

        $item = $this->cart->getCartItem('1');
        $this->assertEquals(2, $item->quantity);
    }

    #[Test]
    public function removing_item_from_cart_does_not_affect_stock(): void
    {
        $before = $this->sparePart->getSparePart('1');
        $stockBefore = (int)$before['stockItemQty'];

        $this->cart->addItem('1', 1);
        $this->cart->removeItem('1');

        $after = $this->sparePart->getSparePart('1');
        $stockAfter = (int)$after['stockItemQty'];

        // Stock should be unchanged — cart removal doesn't touch DB stock
        $this->assertEquals($stockBefore, $stockAfter);
    }

    #[Test]
    public function order_total_matches_cart_total_plus_shipping(): void
    {
        $part = $this->sparePart->getSparePart('5');

        $this->cart->addItem('5', 2);

        $cartTotal = $this->cart->getCartTotal();

        $orderCart = [[
            'itemID'   => '5',
            'quantity' => 2,
            'price'    => (float)$part['price'],
            'weight'   => (float)$part['weight'],
        ]];

        $user = [
            'id'              => 'alex@auto-racing.com',
            'UserID'          => 'alex@auto-racing.com',
            'deliveryAddress' => 'Test Address',
        ];

        $orderId = $this->order->placeOrder(['cart' => $orderCart], $user);
        $orderDetails = $this->order->getOrderByIdAndDealer($orderId, 'alex@auto-racing.com');

        $this->assertEquals($cartTotal, $orderDetails['summary']['totalAmount']);
        $this->assertGreaterThan($cartTotal, $orderDetails['summary']['totalCost']);
    }

    #[Test]
    public function multiple_cart_instances_share_the_same_session_state(): void
    {
        $cart1 = new Cart();
        $cart2 = new Cart();

        $cart1->addItem('1', 1);
        $cart2->addItem('5', 2);

        $this->assertSame($cart1->getCartId(), $cart2->getCartId());
        $this->assertCount(2, $cart1->getCartItems());
        $this->assertCount(2, $cart2->getCartItems());

        $this->assertEquals('1', $cart1->getCartItems()[0]->itemID);
        $this->assertEquals('5', $cart2->getCartItems()[1]->itemID);
    }
}
