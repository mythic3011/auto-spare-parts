<?php

declare(strict_types=1);

namespace App\Api\Domain\Identity\Repository;

use App\Api\Domain\Identity\Entity\User;

interface UserRepository
{
    public function findActiveByEmail(string $email): ?User;
    public function findActiveByPublicId(string $publicId): ?User;
}
