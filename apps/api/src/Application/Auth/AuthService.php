<?php

declare(strict_types=1);

namespace App\Api\Application\Auth;

use App\Api\Domain\Identity\Repository\AuthTokenRepository;
use App\Api\Domain\Identity\Repository\UserRepository;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthTokenRepository $tokens,
    ) {
    }

    /**
     * @return array{token:string,user_public_id:string,role:string}
     */
    public function login(string $email, string $password): array
    {
        $user = $this->users->findActiveByEmail(strtolower(trim($email)));
        if ($user === null || !password_verify($password, $user->passwordHash())) {
            throw new RuntimeException('Invalid credentials.');
        }

        return [
            'token' => $this->tokens->issue($user, 3600),
            'user_public_id' => $user->publicId(),
            'role' => $user->role()->code(),
        ];
    }

    /**
     * @return array{user_public_id:string,role:string}
     */
    public function me(string $bearerToken): array
    {
        $userPublicId = $this->tokens->findUserPublicIdByToken($this->extractBearer($bearerToken));
        if ($userPublicId === null) {
            throw new RuntimeException('Invalid or expired token.');
        }

        $user = $this->users->findActiveByPublicId($userPublicId);
        if ($user === null) {
            throw new RuntimeException('Invalid or expired token.');
        }

        return [
            'user_public_id' => $user->publicId(),
            'role' => $user->role()->code(),
        ];
    }

    public function logout(string $bearerToken): void
    {
        $this->tokens->revokeByToken($this->extractBearer($bearerToken));
    }

    private function extractBearer(string $header): string
    {
        if (str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }

        return trim($header);
    }
}
