<?php

declare(strict_types=1);

namespace App\Api\Infrastructure\Doctrine\Repository;

use App\Api\Domain\Identity\Entity\AuthToken;
use App\Api\Domain\Identity\Entity\User;
use App\Api\Domain\Identity\Repository\AuthTokenRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAuthTokenRepository implements AuthTokenRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function issue(User $user, int $ttlSeconds): string
    {
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT' . $ttlSeconds . 'S'));

        $token = new AuthToken($user, $hash, $expiresAt);
        $this->em->persist($token);
        $this->em->flush();

        return $raw;
    }

    public function findUserPublicIdByToken(string $rawToken): ?string
    {
        $token = $this->findByRawToken($rawToken);
        if (!$token instanceof AuthToken || $token->isExpired(new DateTimeImmutable())) {
            return null;
        }

        return $token->user()->publicId();
    }

    public function revokeByToken(string $rawToken): void
    {
        $token = $this->findByRawToken($rawToken);
        if ($token instanceof AuthToken) {
            $this->em->remove($token);
            $this->em->flush();
        }
    }

    private function findByRawToken(string $rawToken): ?AuthToken
    {
        $token = $this->em->getRepository(AuthToken::class)->findOneBy([
            'tokenHash' => hash('sha256', $rawToken),
        ]);

        return $token instanceof AuthToken ? $token : null;
    }
}
