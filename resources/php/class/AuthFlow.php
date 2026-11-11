<?php

declare(strict_types=1);

require_once __DIR__ . '/AppEnvironment.php';

final class AuthFlow
{
    /**
     * @param array<string, mixed> $input
     * @return array{status: int, body: array<string, mixed>}
     */
    public static function attempt(array $input, User $user, Auth $auth, CSRF $csrf): array
    {
        if (!AppEnvironment::skipsCsrf()) {
            if (!isset($input['csrf_token'])) {
                return self::failure('CSRF token not provided', 403);
            }
            if (!$csrf->validateToken((string) $input['csrf_token'])) {
                return self::failure('Invalid CSRF token', 403);
            }
        }

        $validated = filter_var((string) ($input['username'] ?? ''), FILTER_VALIDATE_EMAIL);
        $username = is_string($validated) ? trim($validated) : '';
        $password = (string) ($input['password'] ?? '');
        if ($username === '' || $password === '') {
            return self::failure('Username or password is empty', 400);
        }

        if ($user->isUserLocked($username)) {
            return self::failure('Account is temporarily locked. Please try again later.', 403);
        }

        $loginResult = $auth->login($username, $password);
        if (isset($loginResult['error'])) {
            $user->logLoginAttempt($username, false);

            return self::failure((string) $loginResult['error'], 401);
        }

        return [
            'status' => 200,
            'body' => $user->establishLogin($loginResult, $username),
        ];
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private static function failure(string $message, int $status): array
    {
        return [
            'status' => $status,
            'body' => ['error' => $message],
        ];
    }
}
