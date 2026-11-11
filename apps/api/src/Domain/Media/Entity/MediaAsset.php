<?php

declare(strict_types=1);

namespace App\Api\Domain\Media\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'media_assets')]
#[ORM\UniqueConstraint(name: 'uniq_media_assets_public_id', columns: ['public_id'])]
#[ORM\UniqueConstraint(name: 'uniq_media_assets_checksum_sha256', columns: ['checksum_sha256'])]
class MediaAsset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(name: 'public_id', type: 'string', length: 48)]
    private string $publicId;

    #[ORM\Column(name: 'storage_disk', type: 'string', length: 32)]
    private string $storageDisk;

    #[ORM\Column(name: 'storage_path', type: 'string', length: 255)]
    private string $storagePath;

    #[ORM\Column(name: 'original_filename', type: 'string', length: 255)]
    private string $originalFilename;

    #[ORM\Column(name: 'mime_type', type: 'string', length: 100)]
    private string $mimeType;

    #[ORM\Column(name: 'checksum_sha256', type: 'string', length: 64)]
    private string $checksumSha256;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $width;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $height;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $publicId,
        string $storageDisk,
        string $storagePath,
        string $originalFilename,
        string $mimeType,
        string $checksumSha256,
        ?int $width,
        ?int $height
    ) {
        $this->publicId = $publicId;
        $this->storageDisk = trim($storageDisk);
        $this->storagePath = trim($storagePath);
        $this->originalFilename = trim($originalFilename);
        $this->mimeType = trim($mimeType);
        $this->checksumSha256 = strtolower(trim($checksumSha256));
        $this->width = $width;
        $this->height = $height;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function id(): ?int { return $this->id; }
    public function publicId(): string { return $this->publicId; }
}
