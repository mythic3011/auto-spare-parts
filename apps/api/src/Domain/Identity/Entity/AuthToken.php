<?php

declare(strict_types=1);

namespace App\Api\Domain\Identity\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_tokens')]
#[ORM\UniqueConstraint(name: 'uniq_auth_tokens_token_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_auth_tokens_user_expires', columns: ['user_id', 'expires_at'])]
class AuthToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 64)]
    private string $tokenHash;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(User $user, string $tokenHash, DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->tokenHash = strtolower($tokenHash);
        $this->expiresAt = $expiresAt;
        $this->createdAt = new DateTimeImmutable();
    }

    public function id(): ?int { return $this->id; }
    public function user(): User { return $this->user; }
    public function tokenHash(): string { return $this->tokenHash; }
    public function expiresAt(): DateTimeImmutable { return $this->expiresAt; }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }
}
