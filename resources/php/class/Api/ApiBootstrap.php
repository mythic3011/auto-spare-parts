<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/HttpPreflight.php';
require_once dirname(__DIR__) . '/Session.php';

final class ApiBootstrap
{
    public static function init(): void
    {
        self::bootConfig();
        Session::begin();

        if (!isset($_SERVER['HTTP_X_REQUEST_ID']) || trim((string) $_SERVER['HTTP_X_REQUEST_ID']) === '') {
            try {
                $_SERVER['HTTP_X_REQUEST_ID'] = bin2hex(random_bytes(16));
            } catch (Throwable $exception) {
                $_SERVER['HTTP_X_REQUEST_ID'] = uniqid('req_', true);
            }
        }

        $origins = (defined('CONFIG') && is_array(CONFIG['origins'] ?? null)) ? CONFIG['origins'] : [];
        $maxAge = defined('CONFIG') ? (int) CONFIG['cors_max_age'] : 600;

        if (self::method() === 'OPTIONS') {
            HttpPreflight::respond($origins, $maxAge);
        }

        HttpPreflight::apply($origins, $maxAge);

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Referrer-Policy: no-referrer');
            header('X-Request-Id: ' . (string) $_SERVER['HTTP_X_REQUEST_ID']);
        }
    }

    public static function method(): string
    {
        return strtoupper(trim((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')));
    }

    private static function bootConfig(): void
    {
        if (defined('CONFIG')) {
            return;
        }

        $configFile = dirname(__DIR__) . '/config.php';
        if (is_file($configFile)) {
            require_once $configFile;
        }
    }
}
