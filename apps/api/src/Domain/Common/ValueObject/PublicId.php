<?php

declare(strict_types=1);

namespace App\Api\Domain\Common\ValueObject;

use InvalidArgumentException;

final readonly class PublicId
{
    private const PREFIX_PATTERN = '/^[a-z]{3,5}$/';
    private const UUID_V7_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value, string $expectedPrefix): self
    {
        self::assertPrefix($expectedPrefix);

        $parts = explode('_', $value, 2);
        if (count($parts) !== 2 || $parts[0] !== $expectedPrefix) {
            throw new InvalidArgumentException('Public ID prefix mismatch.');
        }

        if (!preg_match(self::UUID_V7_PATTERN, $parts[1])) {
            throw new InvalidArgumentException('Public ID must use UUIDv7 format.');
        }

        return new self($value);
    }

    public static function generate(string $prefix): self
    {
        self::assertPrefix($prefix);

        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);
        $uuid = sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );

        return new self($prefix . '_' . $uuid);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function assertPrefix(string $prefix): void
    {
        if (!preg_match(self::PREFIX_PATTERN, $prefix)) {
            throw new InvalidArgumentException('Public ID prefix must be 3-5 lowercase letters.');
        }
    }
}
