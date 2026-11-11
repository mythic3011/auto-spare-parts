<?php

declare(strict_types=1);

namespace Tests\Api\Unit\Entity;

use App\Api\Domain\Catalog\Entity\SparePart;
use App\Api\Domain\Catalog\Entity\SparePartCategory;
use App\Api\Domain\Common\ValueObject\Address;
use App\Api\Domain\Common\ValueObject\PublicId;
use App\Api\Domain\Identity\Entity\Role;
use App\Api\Domain\Identity\Entity\User;
use App\Api\Domain\Media\Entity\MediaAsset;
use App\Api\Domain\Ordering\Entity\Order;
use App\Api\Domain\Ordering\Entity\OrderStatus;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OrderAggregateTest extends TestCase
{
    public function testOrderAggregateMaintainsItemSnapshotAndTotals(): void
    {
        $dealer = new User((string) PublicId::generate('usr'), 'dealer@example.com', 'hash', new Role('dealer', 'Dealer'));
        $manager = new User((string) PublicId::generate('usr'), 'staff@example.com', 'hash', new Role('sales_manager', 'Sales Manager'));
        $status = new OrderStatus('pending', 'Pending');
        $address = new Address('Line 1', null, null, 'Hong Kong', null, 'HK');

        $asset = new MediaAsset(
            (string) PublicId::generate('med'),
            'local',
            'parts/p1.png',
            'p1.png',
            'image/png',
            str_repeat('a', 64),
            1200,
            900
        );

        $category = new SparePartCategory('engine', 'Engine');
        $part = new SparePart(
            (string) PublicId::generate('prt'),
            $category,
            'SKU-001',
            'Fuel Pump',
            'OEM fuel pump',
            '2.500',
            10,
            '200.50',
            $asset
        );

        $order = new Order(
            (string) PublicId::generate('ord'),
            'ORD-0001',
            $dealer,
            $manager,
            $status,
            $address,
            new DateTimeImmutable('+2 days'),
            '15.00'
        );

        $firstItem = $order->addItem($part, 2, '180.00');

        self::assertSame('360.00', $firstItem->lineTotal());
        self::assertSame('375.00', $order->totalAmount());
        self::assertSame($asset, $part->primaryImage());
        self::assertStringStartsWith('ord_', $order->publicId());

        $this->expectException(InvalidArgumentException::class);
        $order->addItem($part, 1, '180.00');
    }

    public function testOrderRejectsInvalidQuantity(): void
    {
        $dealer = new User((string) PublicId::generate('usr'), 'dealer2@example.com', 'hash', new Role('dealer', 'Dealer'));
        $manager = new User((string) PublicId::generate('usr'), 'staff2@example.com', 'hash', new Role('sales_manager', 'Sales Manager'));
        $status = new OrderStatus('pending', 'Pending');
        $address = new Address('Line 1', null, null, 'Hong Kong', null, 'HK');
        $category = new SparePartCategory('electrical', 'Electrical');

        $part = new SparePart(
            (string) PublicId::generate('prt'),
            $category,
            'SKU-002',
            'Battery',
            null,
            '4.500',
            7,
            '600.00'
        );

        $order = new Order(
            (string) PublicId::generate('ord'),
            'ORD-0002',
            $dealer,
            $manager,
            $status,
            $address,
            new DateTimeImmutable('+3 days'),
            '20.00'
        );

        $this->expectException(InvalidArgumentException::class);
        $order->addItem($part, 0);
    }
}
