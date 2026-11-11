<?php

declare(strict_types=1);

final class ApiResponder
{
    /**
     * @param callable():void $callback
     */
    public static function run(callable $callback, string $genericErrorMessage = 'Request failed'): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            error_log($genericErrorMessage . ': ' . $exception->getMessage());
            $statusCode = (int) $exception->getCode();
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 500;
            }
            ResponseHandler::error($genericErrorMessage, $statusCode);
        }
    }
}
