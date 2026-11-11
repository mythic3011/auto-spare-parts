<?php

declare(strict_types=1);

/**
 * CORS preflight. Origins are an allowlist. The preflight response is cacheable;
 * actual API responses stay no-store.
 */
final class HttpPreflight
{
    public static function isOptions(): bool
    {
        return strtoupper(trim((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))) === 'OPTIONS';
    }

    /**
     * @param list<mixed> $allowedOrigins
     * @return array<string, string>
     */
    public static function headers(string $origin, array $allowedOrigins, int $maxAge = 600): array
    {
        $headers = [
            'Vary' => 'Origin',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Request-Id, X-Test-Env, X-Testing',
            'Access-Control-Max-Age' => (string) max(0, $maxAge),
        ];

        if ($origin !== '' && self::isAllowed($origin, $allowedOrigins)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        return $headers;
    }

    /**
     * @param list<mixed> $allowedOrigins
     */
    public static function apply(array $allowedOrigins, int $maxAge = 600): void
    {
        if (headers_sent()) {
            return;
        }

        foreach (self::headers(self::origin(), $allowedOrigins, $maxAge) as $name => $value) {
            header($name . ': ' . $value);
        }
    }

    /**
     * @param list<mixed> $allowedOrigins
     */
    public static function respond(array $allowedOrigins, int $maxAge = 600): never
    {
        if (headers_sent()) {
            throw new RuntimeException('Cannot send preflight after output has started');
        }

        $maxAge = max(0, $maxAge);
        self::apply($allowedOrigins, $maxAge);
        http_response_code(204);
        header('Content-Length: 0');
        header('Cache-Control: public, max-age=' . $maxAge);
        exit;
    }

    /**
     * @param list<mixed> $allowedOrigins
     */
    private static function isAllowed(string $origin, array $allowedOrigins): bool
    {
        foreach ($allowedOrigins as $allowed) {
            if ($origin === trim((string) $allowed) && $origin !== '') {
                return true;
            }
        }

        return false;
    }

    private static function origin(): string
    {
        return trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    }
}
