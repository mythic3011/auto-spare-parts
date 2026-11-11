<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/RequestToken.php';
require_once __DIR__ . '/ApiBootstrap.php';

final class ApiRequest
{
    public function method(): string
    {
        return ApiBootstrap::method();
    }

    public function action(): string
    {
        $queryAction = trim((string) ($_GET['action'] ?? ''));

        if ($queryAction !== '') {
            return $queryAction;
        }

        $body = $this->jsonBodyOrNull();
        if (is_array($body) && isset($body['action'])) {
            return trim((string) $body['action']);
        }

        return '';
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonBody(): array
    {
        $raw = (string) file_get_contents('php://input');
        if (trim($raw) === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid JSON input', 400, $exception);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid JSON input', 400);
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    public function input(): array
    {
        if ($_POST !== []) {
            /** @var array<string, mixed> $postData */
            $postData = $_POST;

            return $postData;
        }

        return $this->jsonBody();
    }

    public function scalar(string $key): string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    public function authToken(): string
    {
        $token = RequestToken::fromAuthorizationHeader();
        if (is_string($token) && $token !== '') {
            return $token;
        }

        foreach (['token', 'auth_token', 'jwt', 'userToken'] as $cookieName) {
            $cookieToken = trim((string) ($_COOKIE[$cookieName] ?? ''));
            if ($cookieToken !== '') {
                return $cookieToken;
            }
        }

        foreach (['token', 'auth_token', 'jwt', 'userToken'] as $sessionKey) {
            $sessionToken = trim((string) ($_SESSION[$sessionKey] ?? ''));
            if ($sessionToken !== '') {
                return $sessionToken;
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function jsonBodyOrNull(): ?array
    {
        try {
            return $this->jsonBody();
        } catch (RuntimeException $exception) {
            return null;
        }
    }
}
