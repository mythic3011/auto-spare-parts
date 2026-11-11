<?php

declare(strict_types=1);

final class HttpJson
{
    public static function send(mixed $payload, int $status = 200): never
    {
        if ($status < 100 || $status > 599) {
            $status = 500;
        }

        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store');
        } else {
            http_response_code($status);
        }

        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function result(array $payload, int $status = 200): never
    {
        self::send(['result' => $payload], $status);
    }
}
