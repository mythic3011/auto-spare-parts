<?php

declare(strict_types=1);

/**
 * ValidationHelper - Input validation and sanitization
 *
 * Provides utilities for validating common input types and formats
 */
class ValidationHelper
{
    /**
     * @var array<string, array<string>>
     */
    private array $errors = [];

    /**
     * Get all validation errors
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation passed (no errors)
     */
    public function passed(): bool
    {
        return empty($this->errors);
    }

    /**
     * Add an error for a field
     */
    public function addError(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        return $this;
    }

    /**
     * Validate required field
     */
    public function required(string $field, mixed $value): self
    {
        if (empty($value) || (is_string($value) && trim($value) === '')) {
            $this->addError($field, ucfirst($field) . ' is required');
        }
        return $this;
    }

    /**
     * Validate email format
     */
    public function email(string $field, string $value): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, ucfirst($field) . ' must be a valid email address');
        }
        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength(string $field, string $value, int $length): self
    {
        if (!empty($value) && strlen($value) < $length) {
            $this->addError($field, ucfirst($field) . ' must be at least ' . $length . ' characters');
        }
        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength(string $field, string $value, int $length): self
    {
        if (!empty($value) && strlen($value) > $length) {
            $this->addError($field, ucfirst($field) . ' must not exceed ' . $length . ' characters');
        }
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric(string $field, mixed $value): self
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->addError($field, ucfirst($field) . ' must be a number');
        }
        return $this;
    }

    /**
     * Validate minimum value
     */
    public function min(string $field, mixed $value, int|float $min): self
    {
        if ($value !== '' && $value !== null && $value < $min) {
            $this->addError($field, ucfirst($field) . ' must be at least ' . $min);
        }
        return $this;
    }

    /**
     * Validate maximum value
     */
    public function max(string $field, mixed $value, int|float $max): self
    {
        if ($value !== '' && $value !== null && $value > $max) {
            $this->addError($field, ucfirst($field) . ' must not exceed ' . $max);
        }
        return $this;
    }

    /**
     * Validate in allowed list
     *
     * @param array<string> $allowed
     */
    public function in(string $field, string $value, array $allowed): self
    {
        if (!empty($value) && !in_array($value, $allowed, true)) {
            $this->addError($field, ucfirst($field) . ' has an invalid value');
        }
        return $this;
    }

    /**
     * Validate array contains required keys
     *
     * @param array<string> $keys
     */
    public function arrayKeys(string $field, array $data, array $keys): self
    {
        foreach ($keys as $key) {
            if (!isset($data[$key]) || empty($data[$key])) {
                $this->addError($field, 'Missing required field: ' . $key);
            }
        }
        return $this;
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString(string $input): string
    {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail(string $email): string
    {
        return strtolower(trim(filter_var($email, FILTER_SANITIZE_EMAIL)));
    }

    /**
     * Sanitize integer
     */
    public static function sanitizeInt(mixed $value): int
    {
        return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float
     */
    public static function sanitizeFloat(mixed $value): float
    {
        return (float)filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public static function isBoundedText(string $value, int $maxLength): bool
    {
        $value = trim($value);

        return $value !== ''
            && mb_strlen($value) <= $maxLength
            && preg_match('/[<>]/', $value) !== 1;
    }

    public static function boundedText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if (!self::isBoundedText($value, $maxLength)) {
            throw new InvalidArgumentException('Invalid profile field');
        }

        return $value;
    }

    public static function digits(string $value, bool $required = true): string
    {
        if ($value === '' && !$required) {
            return '';
        }
        if (preg_match('/^\d{8,15}$/', $value) !== 1) {
            throw new InvalidArgumentException($required ? 'Invalid contact number' : 'Invalid fax number');
        }

        return $value;
    }
}
