<?php

declare(strict_types=1);

final class RoleName
{
    public static function normalize(string $role): string
    {
        return strtolower(str_replace([' ', '_', '-'], '', trim($role)));
    }

    public static function is(string $role, string $expected): bool
    {
        $actual = self::normalize($role);
        $required = self::normalize($expected);

        return $actual !== '' && $required !== '' && $actual === $required;
    }

    public static function home(string $role): string
    {
        return match (self::normalize($role)) {
            'dealer' => './page/Dealer/viewSparePart.php',
            'salesmanager' => './page/Staff/order.html',
            default => 'index.php',
        };
    }
}
