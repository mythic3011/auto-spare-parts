<?php

declare(strict_types=1);

final class RequestToken
{
    public static function fromAuthorizationHeader(): ?string
    {
        $headers = function_exists('getallheaders') ? (array) getallheaders() : [];
        $apache = function_exists('apache_request_headers') ? (array) apache_request_headers() : [];
        $candidates = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
            $headers['Authorization'] ?? null,
            $headers['authorization'] ?? null,
            $apache['Authorization'] ?? null,
        ];

        $header = '';
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $header = trim($candidate);
                break;
            }
        }

        if ($header === '') {
            return null;
        }

        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $matches) === 1) {
            return $matches[1];
        }

        return preg_match('/^\S+$/', $header) === 1 ? $header : null;
    }
}
