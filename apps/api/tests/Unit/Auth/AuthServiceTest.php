<?php

declare(strict_types=1);

namespace Tests\Api\Unit\Auth;

use App\Api\Application\Auth\AuthService;
use App\Api\Domain\Identity\Entity\Role;
use App\Api\Domain\Identity\Entity\User;
use App\Api\Domain\Identity\Repository\AuthTokenRepository;
use App\Api\Domain\Identity\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuthServiceTest extends TestCase
{
    public function testLoginMeLogoutFlow(): void
    {
        $role = new Role('dealer', 'Dealer');
        $user = new User('usr_018f4f2d-8f03-7e16-8ad1-1b565b7f8e8a', 'dealer@example.com', password_hash('secret', PASSWORD_DEFAULT), $role);

        $users = new InMemoryUserRepository([$user]);
        $tokens = new InMemoryAuthTokenRepository();
        $service = new AuthService($users, $tokens);

        $login = $service->login('dealer@example.com', 'secret');
        self::assertArrayHasKey('token', $login);
        self::assertSame('dealer', $login['role']);

        $me = $service->me('Bearer ' . $login['token']);
        self::assertSame($user->publicId(), $me['user_public_id']);

        $service->logout('Bearer ' . $login['token']);
        $this->expectException(RuntimeException::class);
        $service->me('Bearer ' . $login['token']);
    }

    public function testLoginRejectsBadPassword(): void
    {
        $role = new Role('dealer', 'Dealer');
        $user = new User('usr_018f4f2d-8f03-7e16-8ad1-1b565b7f8e8a', 'dealer@example.com', password_hash('secret', PASSWORD_DEFAULT), $role);

        $service = new AuthService(new InMemoryUserRepository([$user]), new InMemoryAuthTokenRepository());
        $this->expectException(RuntimeException::class);
        $service->login('dealer@example.com', 'wrong-password');
    }
}

final class InMemoryUserRepository implements UserRepository
{
    /** @param User[] $users */
    public function __construct(private array $users)
    {
    }

    public function findActiveByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email() === strtolower($email) && $user->isActive()) {
                return $user;
            }
        }

        return null;
    }

    public function findActiveByPublicId(string $publicId): ?User
    {
        foreach ($this->users as $user) {
            if ($user->publicId() === $publicId && $user->isActive()) {
                return $user;
            }
        }

        return null;
    }
}

final class InMemoryAuthTokenRepository implements AuthTokenRepository
{
    /** @var array<string,string> */
    private array $tokens = [];

    public function issue(User $user, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(8));
        $this->tokens[$token] = $user->publicId();
        return $token;
    }

    public function findUserPublicIdByToken(string $rawToken): ?string
    {
        return $this->tokens[$rawToken] ?? null;
    }

    public function revokeByToken(string $rawToken): void
    {
        unset($this->tokens[$rawToken]);
    }
}
