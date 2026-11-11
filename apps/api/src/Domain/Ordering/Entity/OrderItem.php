<?php

declare(strict_types=1);

namespace App\Api\Domain\Ordering\Entity;

use App\Api\Domain\Catalog\Entity\SparePart;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
#[ORM\UniqueConstraint(name: 'uniq_order_part', columns: ['order_id', 'spare_part_id'])]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\ManyToOne(targetEntity: SparePart::class)]
    #[ORM\JoinColumn(name: 'spare_part_id', referencedColumnName: 'id', nullable: false)]
    private SparePart $sparePart;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'unit_price', type: 'decimal', precision: 12, scale: 2)]
    private string $unitPrice;

    public function __construct(Order $order, SparePart $sparePart, int $quantity, string $unitPrice)
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Order item quantity must be positive.');
        }

        if ((float) $unitPrice < 0.0) {
            throw new InvalidArgumentException('Order item unit price cannot be negative.');
        }

        $this->order = $order;
        $this->sparePart = $sparePart;
        $this->quantity = $quantity;
        $this->unitPrice = number_format((float) $unitPrice, 2, '.', '');
    }

    public function sparePart(): SparePart { return $this->sparePart; }
    public function quantity(): int { return $this->quantity; }
    public function unitPrice(): string { return $this->unitPrice; }
    public function lineTotal(): string { return number_format($this->quantity * (float) $this->unitPrice, 2, '.', ''); }
}
