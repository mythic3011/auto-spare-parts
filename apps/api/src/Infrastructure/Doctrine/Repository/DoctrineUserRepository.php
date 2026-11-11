<?php

declare(strict_types=1);

namespace App\Api\Infrastructure\Doctrine\Repository;

use App\Api\Domain\Identity\Entity\User;
use App\Api\Domain\Identity\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function findActiveByEmail(string $email): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy([
            'email' => strtolower(trim($email)),
            'isActive' => true,
        ]);
    }

    public function findActiveByPublicId(string $publicId): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy([
            'publicId' => $publicId,
            'isActive' => true,
        ]);
    }
}
