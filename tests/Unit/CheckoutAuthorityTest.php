<?php

declare(strict_types=1);

namespace Tests\Unit;

use DatabaseAdapter;
use Order;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shipping;

final class CheckoutAuthorityTest extends TestCase
{
    private const USER = ['id' => 'dealer', 'deliveryAddress' => 'Test address'];

    public function testCatalogValuesOverrideClientPricesAndWeights(): void
    {
        $db = $this->createMock(DatabaseAdapter::class);
        $db->expects(self::once())->method('beginTransaction');
        $db->expects(self::once())->method('commit');
        $db->expects(self::never())->method('rollback');
        $db->expects(self::once())->method('fetchOne')->with(
            'SELECT price, weight, stockItemQty FROM Item WHERE sparePartNum = ? FOR UPDATE', ['1']
        )->willReturn(['price' => '12.35', 'weight' => '2.5', 'stockItemQty' => 10]);
        $db->method('fetchAll')->willReturn([['salesManagerID' => 'manager']]);
        $db->method('lastInsertId')->willReturn(42);
        $writes = [];
        $db->method('execute')->willReturnCallback(static function (string $sql, array $params) use (&$writes): int {
            $writes[] = [$sql, $params];
            return 1;
        });
        $shipping = $this->createMock(Shipping::class);
        $quotes = [];
        $shipping->method('calculateShippingCost')->willReturnCallback(static function ($mode, $value) use (&$quotes): float {
            $quotes[$mode] = $value;
            return 300.0;
        });
        $order = new Order($db, $shipping);
        $id = $order->placeOrder(['cart' => [['itemID' => '1', 'quantity' => 2, 'price' => -1, 'weight' => 0]]], self::USER);
        self::assertSame('42', $id);
        self::assertSame(['weight' => 5.0, 'quantity' => 2], $quotes);
        self::assertStringContainsString('INSERT INTO Orders (publicID,', $writes[0][0]);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $writes[0][1][0]);
        self::assertSame(['42', '1', 2, '12.35'], $writes[2][1]);
    }

    public static function invalidCarts(): iterable
    {
        yield 'fraction' => [[['itemID' => '1', 'quantity' => 1.5]]];
        yield 'negative' => [[['itemID' => '1', 'quantity' => -1]]];
        yield 'zero' => [[['itemID' => '1', 'quantity' => 0]]];
        yield 'missing quantity' => [[['itemID' => '1']]];
        yield 'invalid item' => [[null]];
        yield 'duplicate' => [[['itemID' => '1', 'quantity' => 1], ['itemID' => '1', 'quantity' => 1]]];
    }

    #[DataProvider('invalidCarts')]
    public function testRejectsInvalidCartBeforeCatalogOrShippingCalls(array $cart): void
    {
        $db = $this->createMock(DatabaseAdapter::class);
        $db->expects(self::never())->method('fetchOne');
        $db->expects(self::never())->method('execute');
        $db->expects(self::once())->method('rollback');
        $shipping = $this->createMock(Shipping::class);
        $shipping->expects(self::never())->method('calculateShippingCost');
        $this->expectException(\InvalidArgumentException::class);
        (new Order($db, $shipping))->placeOrder(['cart' => $cart], self::USER);
    }

    public function testShippingFailureRollsBackWithoutWritingOrderOrStock(): void
    {
        $db = $this->createMock(DatabaseAdapter::class);
        $db->method('fetchOne')->willReturn(['price' => '12.35', 'weight' => 1, 'stockItemQty' => 10]);
        $db->expects(self::never())->method('execute');
        $db->expects(self::never())->method('commit');
        $db->expects(self::once())->method('rollback');
        $shipping = $this->createMock(Shipping::class);
        $shipping->method('calculateShippingCost')->willThrowException(new \RuntimeException('Shipping unavailable'));
        $this->expectExceptionMessage('Shipping unavailable');
        (new Order($db, $shipping))->placeOrder(['cart' => [['itemID' => '1', 'quantity' => 1]]], self::USER);
    }
}
