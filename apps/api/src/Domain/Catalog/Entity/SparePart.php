<?php

declare(strict_types=1);

namespace App\Api\Domain\Catalog\Entity;

use App\Api\Domain\Media\Entity\MediaAsset;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'spare_parts')]
#[ORM\UniqueConstraint(name: 'uniq_spare_parts_public_id', columns: ['public_id'])]
#[ORM\UniqueConstraint(name: 'uniq_spare_parts_sku', columns: ['sku'])]
class SparePart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(name: 'public_id', type: 'string', length: 48)]
    private string $publicId;

    #[ORM\ManyToOne(targetEntity: SparePartCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false)]
    private SparePartCategory $category;

    #[ORM\ManyToOne(targetEntity: MediaAsset::class)]
    #[ORM\JoinColumn(name: 'primary_image_asset_id', referencedColumnName: 'id', nullable: true)]
    private ?MediaAsset $primaryImage;

    #[ORM\Column(type: 'string', length: 50)]
    private string $sku;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(name: 'weight_kg', type: 'decimal', precision: 10, scale: 3)]
    private string $weightKg;

    #[ORM\Column(name: 'stock_quantity', type: 'integer')]
    private int $stockQuantity;

    #[ORM\Column(name: 'unit_price', type: 'decimal', precision: 12, scale: 2)]
    private string $unitPrice;

    public function __construct(
        string $publicId,
        SparePartCategory $category,
        string $sku,
        string $name,
        ?string $description,
        string $weightKg,
        int $stockQuantity,
        string $unitPrice,
        ?MediaAsset $primaryImage = null
    ) {
        if ($stockQuantity < 0) {
            throw new InvalidArgumentException('Stock quantity cannot be negative.');
        }

        if ((float) $weightKg <= 0.0 || (float) $unitPrice < 0.0) {
            throw new InvalidArgumentException('Weight must be positive and unit price cannot be negative.');
        }

        $this->publicId = $publicId;
        $this->category = $category;
        $this->primaryImage = $primaryImage;
        $this->sku = trim($sku);
        $this->name = trim($name);
        $this->description = $description !== null ? trim($description) : null;
        $this->weightKg = number_format((float) $weightKg, 3, '.', '');
        $this->stockQuantity = $stockQuantity;
        $this->unitPrice = number_format((float) $unitPrice, 2, '.', '');
    }

    public function id(): ?int { return $this->id; }
    public function publicId(): string { return $this->publicId; }
    public function sku(): string { return $this->sku; }
    public function unitPrice(): string { return $this->unitPrice; }
    public function primaryImage(): ?MediaAsset { return $this->primaryImage; }
}
