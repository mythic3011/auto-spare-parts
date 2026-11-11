<?php

declare(strict_types=1);

final class AppEnvironment
{
    public static function name(): string
    {
        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV');

        return is_string($env) && $env !== '' ? $env : 'production';
    }

    public static function isTesting(): bool
    {
        return self::name() === 'testing' || defined('TESTING');
    }

    public static function skipsCsrf(): bool
    {
        return self::name() === 'development' || self::isTesting();
    }
}
