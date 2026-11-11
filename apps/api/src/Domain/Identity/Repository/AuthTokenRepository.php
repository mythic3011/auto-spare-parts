<?php

declare(strict_types=1);

namespace App\Api\Domain\Identity\Repository;

use App\Api\Domain\Identity\Entity\User;

interface AuthTokenRepository
{
    public function issue(User $user, int $ttlSeconds): string;
    public function findUserPublicIdByToken(string $rawToken): ?string;
    public function revokeByToken(string $rawToken): void;
}
