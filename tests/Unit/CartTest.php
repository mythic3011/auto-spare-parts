<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Cart;
use Exception;

class CartTest extends TestCase
{
    private Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCartSession();
        $this->cart = new Cart();
    }

    protected function tearDown(): void
    {
        $this->resetCartSession();
        parent::tearDown();
    }

    private function resetCartSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset(
            $_SESSION['cartId'],
            $_SESSION['cart_id'],
            $_SESSION['cart'],
            $_SESSION['cart_data'],
            $_SESSION['cartItems'],
            $_SESSION['cart_items']
        );
        Cart::resetStateForTesting();
    }

    #[Test]
    public function it_generates_cart_id_for_a_new_session(): void
    {
        $cartId = $this->cart->getCartId();

        $this->assertNotEmpty($cartId);
        $this->assertIsString($cartId);
    }

    #[Test]
    public function it_adds_item_to_cart(): void
    {
        $result = $this->cart->addItem('1', 2);

        $this->assertTrue($result);
        $this->assertCount(1, $this->cart->getCartItems());
    }

    #[Test]
    public function it_throws_exception_for_nonexistent_item(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Item not found');

        $this->cart->addItem('999', 1);
    }

    #[Test]
    public function it_throws_exception_for_insufficient_stock(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not enough stock');

        // Item 1 has stock of 3
        $this->cart->addItem('1', 999);
    }

    #[Test]
    public function it_updates_quantity_for_existing_item(): void
    {
        $this->cart->addItem('1', 1);
        $this->cart->addItem('1', 2); // Should update quantity to 3

        $items = $this->cart->getCartItems();
        $this->assertCount(1, $items);
        $this->assertEquals(3, $items[0]->quantity);
    }

    #[Test]
    public function it_updates_item_quantity(): void
    {
        $this->cart->addItem('1', 1);
        $updatedItem = $this->cart->updateQuantity('1', 2);

        $this->assertEquals(2, $updatedItem->quantity);
    }

    #[Test]
    public function it_throws_exception_when_updating_nonexistent_item(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Item not found');

        $this->cart->updateQuantity('999', 1);
    }

    #[Test]
    public function it_removes_item_from_cart(): void
    {
        $this->cart->addItem('1', 1);
        $this->cart->addItem('5', 2);

        $this->assertCount(2, $this->cart->getCartItems());

        $this->cart->removeItem('1');

        $this->assertCount(1, $this->cart->getCartItems());
    }

    #[Test]
    public function it_calculates_cart_total(): void
    {
        $this->cart->addItem('1', 1); // Price: 5000
        $this->cart->addItem('5', 2); // Price: 500 x 2 = 1000

        $total = $this->cart->getCartTotal();

        $this->assertEquals(6000, $total);
    }

    #[Test]
    public function it_clears_cart(): void
    {
        $this->cart->addItem('1', 1);
        $this->cart->addItem('5', 2);

        $this->assertCount(2, $this->cart->getCartItems());

        $this->cart->clearCart();

        $this->assertCount(0, $this->cart->getCartItems());
    }

    #[Test]
    public function it_gets_specific_cart_item(): void
    {
        $this->cart->addItem('1', 2);

        $item = $this->cart->getCartItem('1');

        $this->assertNotNull($item);
        $this->assertEquals('1', $item->itemID);
        $this->assertEquals(2, $item->quantity);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_cart_item(): void
    {
        $item = $this->cart->getCartItem('999');

        $this->assertNull($item);
    }

    #[Test]
    public function it_reuses_cart_state_from_the_same_session(): void
    {
        $this->cart->addItem('1', 1);
        $firstCartId = $this->cart->getCartId();

        $nextCart = new Cart();
        $items = $nextCart->getCartItems();

        $this->assertSame($firstCartId, $nextCart->getCartId());
        $this->assertCount(1, $items);
        $this->assertEquals('1', $items[0]->itemID);
        $this->assertEquals(1, $items[0]->quantity);
    }
}
