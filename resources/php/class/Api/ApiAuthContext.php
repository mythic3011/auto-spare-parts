<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/RoleName.php';

final class ApiAuthContext
{
    /**
     * @return array<string, mixed>
     */
    public function requireUser(Auth $auth, ApiRequest $request): array
    {
        $token = $request->authToken();
        if ($token === '') {
            throw new RuntimeException('Authentication required', 401);
        }

        $user = $auth->validateToken($token);
        if (!is_array($user) || $this->userIdentifier($user) === '') {
            throw new RuntimeException('Invalid or expired token', 401);
        }

        return $this->normalizeUserRecord($user);
    }

    /**
     * @param array<string, mixed> $user
     */
    public function requireRole(array $user, string $requiredRole): void
    {
        $role = trim((string) ($user['role'] ?? $user['Role'] ?? $user['UserRole'] ?? ''));
        if (!RoleName::is($role, $requiredRole)) {
            throw new RuntimeException('Forbidden', 403);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    public function userIdentifier(array $user): string
    {
        foreach (['UserID', 'userID', 'userId', 'id', 'username', 'email'] as $key) {
            $value = trim((string) ($user[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function normalizeUserRecord(array $user): array
    {
        $identifier = $this->userIdentifier($user);
        $role = trim((string) ($user['role'] ?? $user['Role'] ?? $user['UserRole'] ?? ''));
        unset($user['password'], $user['Password']);

        $user['UserID'] = $identifier;
        $user['id'] = $user['id'] ?? $identifier;
        $user['username'] = $user['username'] ?? $identifier;
        $user['email'] = $user['email'] ?? (str_contains($identifier, '@') ? $identifier : '');
        $user['role'] = $role;

        return $user;
    }
}
