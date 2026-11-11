<?php

declare(strict_types=1);

namespace App\Api\Domain\Identity\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_public_id', columns: ['public_id'])]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(name: 'public_id', type: 'string', length: 48)]
    private string $publicId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false)]
    private Role $role;

    #[ORM\Column(name: 'is_active', type: 'boolean')]
    private bool $isActive;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: DealerProfile::class, cascade: ['persist', 'remove'])]
    private ?DealerProfile $dealerProfile = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: SalesManagerProfile::class, cascade: ['persist', 'remove'])]
    private ?SalesManagerProfile $salesManagerProfile = null;

    public function __construct(string $publicId, string $email, string $passwordHash, Role $role)
    {
        $this->publicId = $publicId;
        $this->email = strtolower(trim($email));
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function id(): ?int { return $this->id; }
    public function publicId(): string { return $this->publicId; }
    public function email(): string { return $this->email; }
    public function passwordHash(): string { return $this->passwordHash; }
    public function role(): Role { return $this->role; }
    public function isActive(): bool { return $this->isActive; }
    public function dealerProfile(): ?DealerProfile { return $this->dealerProfile; }
    public function salesManagerProfile(): ?SalesManagerProfile { return $this->salesManagerProfile; }

    public function assignDealerProfile(DealerProfile $profile): void
    {
        $this->dealerProfile = $profile;
        $this->salesManagerProfile = null;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function assignSalesManagerProfile(SalesManagerProfile $profile): void
    {
        $this->salesManagerProfile = $profile;
        $this->dealerProfile = null;
        $this->updatedAt = new DateTimeImmutable();
    }
}
