<?php

declare(strict_types=1);

namespace App\Api\Domain\Ordering\Entity;

use App\Api\Domain\Common\ValueObject\Address;
use App\Api\Domain\Catalog\Entity\SparePart;
use App\Api\Domain\Identity\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\UniqueConstraint(name: 'uniq_orders_public_id', columns: ['public_id'])]
#[ORM\UniqueConstraint(name: 'uniq_orders_order_number', columns: ['order_number'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(name: 'public_id', type: 'string', length: 48)]
    private string $publicId;

    #[ORM\Column(name: 'order_number', type: 'string', length: 32)]
    private string $orderNumber;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'dealer_user_id', referencedColumnName: 'id', nullable: false)]
    private User $dealer;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sales_manager_user_id', referencedColumnName: 'id', nullable: false)]
    private User $salesManager;

    #[ORM\ManyToOne(targetEntity: OrderStatus::class)]
    #[ORM\JoinColumn(name: 'status_id', referencedColumnName: 'id', nullable: false)]
    private OrderStatus $status;

    #[ORM\Embedded(class: Address::class, columnPrefix: 'delivery_')]
    private Address $deliveryAddress;

    #[ORM\Column(name: 'delivery_date', type: 'date_immutable')]
    private DateTimeImmutable $deliveryDate;

    #[ORM\Column(name: 'shipping_cost', type: 'decimal', precision: 12, scale: 2)]
    private string $shippingCost;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /** @var list<OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private array $items = [];

    public function __construct(
        string $publicId,
        string $orderNumber,
        User $dealer,
        User $salesManager,
        OrderStatus $status,
        Address $deliveryAddress,
        DateTimeImmutable $deliveryDate,
        string $shippingCost
    ) {
        if ((float) $shippingCost < 0.0) {
            throw new InvalidArgumentException('Shipping cost cannot be negative.');
        }

        $this->publicId = $publicId;
        $this->orderNumber = trim($orderNumber);
        $this->dealer = $dealer;
        $this->salesManager = $salesManager;
        $this->status = $status;
        $this->deliveryAddress = $deliveryAddress;
        $this->deliveryDate = $deliveryDate;
        $this->shippingCost = number_format((float) $shippingCost, 2, '.', '');
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addItem(SparePart $sparePart, int $quantity, ?string $unitPrice = null): OrderItem
    {
        foreach ($this->items as $item) {
            if ($item->sparePart() === $sparePart) {
                throw new InvalidArgumentException('Duplicate spare part in order is not allowed.');
            }
        }

        $resolvedUnitPrice = $unitPrice ?? $sparePart->unitPrice();
        $orderItem = new OrderItem($this, $sparePart, $quantity, $resolvedUnitPrice);
        $this->items[] = $orderItem;
        $this->updatedAt = new DateTimeImmutable();

        return $orderItem;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function publicId(): string
    {
        return $this->publicId;
    }

    public function totalAmount(): string
    {
        $itemTotal = 0.0;
        foreach ($this->items as $item) {
            $itemTotal += (float) $item->lineTotal();
        }

        return number_format($itemTotal + (float) $this->shippingCost, 2, '.', '');
    }
}
