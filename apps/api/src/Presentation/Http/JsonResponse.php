<?php

declare(strict_types=1);

namespace App\Api\Presentation\Http;

final class JsonResponse
{
    /**
     * @param array<string,mixed> $data
     */
    public static function success(array $data, int $status = 200): never
    {
        self::send($status, ['success' => true, 'data' => $data]);
        exit;
    }

    public static function error(string $message, int $status): never
    {
        self::send($status, ['success' => false, 'error' => $message]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function send(int $status, array $payload): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }
}
