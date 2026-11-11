<?php

declare(strict_types=1);

use App\Api\Application\Auth\AuthService;
use App\Api\Infrastructure\Doctrine\Repository\DoctrineAuthTokenRepository;
use App\Api\Infrastructure\Doctrine\Repository\DoctrineUserRepository;
use App\Api\Presentation\Http\JsonResponse;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../resources/php/class/config.php';
require_once __DIR__ . '/../../../resources/php/class/HttpPreflight.php';

$allowedOrigins = (defined('CONFIG') && is_array(CONFIG['origins'] ?? null)) ? CONFIG['origins'] : [];
$preflightMaxAge = defined('CONFIG') ? (int) (CONFIG['cors_max_age'] ?? 600) : 600;
if (HttpPreflight::isOptions()) {
    HttpPreflight::respond($allowedOrigins, $preflightMaxAge);
}
HttpPreflight::apply($allowedOrigins, $preflightMaxAge);

function authService(): AuthService
{
    $entityManager = require __DIR__ . '/../bootstrap/doctrine.php';
    return new AuthService(
        new DoctrineUserRepository($entityManager),
        new DoctrineAuthTokenRepository($entityManager),
    );
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

try {
    if ($uri === '/api/v1/auth/login' && $method === 'POST') {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload) || !is_string($payload['email'] ?? null)
            || !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)
            || !is_string($payload['password'] ?? null) || $payload['password'] === '') {
            JsonResponse::error('Invalid request payload.', 400);
        }

        JsonResponse::success(authService()->login((string) $payload['email'], (string) $payload['password']));
    }

    if ($uri === '/api/v1/auth/me' && $method === 'GET') {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($header === '') {
            JsonResponse::error('Authorization header is required.', 401);
        }

        JsonResponse::success(authService()->me($header));
    }

    if ($uri === '/api/v1/auth/logout' && $method === 'POST') {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($header === '') {
            JsonResponse::error('Authorization header is required.', 401);
        }

        authService()->logout($header);
        JsonResponse::success(['revoked' => true]);
    }

    JsonResponse::error('Not found.', 404);
} catch (\RuntimeException $e) {
    $message = $e->getMessage();
    if ($message === 'Invalid credentials.' || $message === 'Invalid or expired token.') {
        JsonResponse::error($message, 401);
    }

    JsonResponse::error('Request could not be completed.', 400);
} catch (\Throwable) {
    JsonResponse::error('Internal server error.', 500);
}
