<?php

declare(strict_types=1);

require_once __DIR__ . '/HttpJson.php';
require_once __DIR__ . '/User.php';

final class StaffAccess
{
    public static function requireSalesManager(): void
    {
        try {
            (new User())->requireRole('SalesManager');
        } catch (RuntimeException $exception) {
            $status = (int) $exception->getCode();
            HttpJson::send(
                ['status' => 'error', 'message' => 'Forbidden'],
                $status >= 400 && $status <= 599 ? $status : 401
            );
        }
    }
}
