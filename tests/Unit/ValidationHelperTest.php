<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidationHelper;

class ValidationHelperTest extends TestCase
{
    private ValidationHelper $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidationHelper();
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $this->validator->required('username', '');

        $this->assertFalse($this->validator->passed());
        $this->assertArrayHasKey('username', $this->validator->getErrors());
    }

    #[Test]
    public function it_passes_required_validation_with_value(): void
    {
        $this->validator->required('username', 'john_doe');

        $this->assertTrue($this->validator->passed());
    }

    #[Test]
    public function it_validates_email_format(): void
    {
        $this->validator->email('email', 'invalid-email');

        $this->assertFalse($this->validator->passed());
        $this->assertArrayHasKey('email', $this->validator->getErrors());
    }

    #[Test]
    public function it_passes_email_validation_with_valid_email(): void
    {
        $this->validator->email('email', 'user@example.com');

        $this->assertTrue($this->validator->passed());
    }

    #[Test]
    public function it_validates_minimum_length(): void
    {
        $this->validator->minLength('password', 'short', 8);

        $this->assertFalse($this->validator->passed());
    }

    #[Test]
    public function it_passes_minimum_length_validation(): void
    {
        $this->validator->minLength('password', 'longenough', 8);

        $this->assertTrue($this->validator->passed());
    }

    #[Test]
    public function it_validates_maximum_length(): void
    {
        $this->validator->maxLength('username', 'verylongusernamethatexceedslimit', 10);

        $this->assertFalse($this->validator->passed());
    }

    #[Test]
    public function it_validates_numeric_values(): void
    {
        $this->validator->numeric('age', 'not-a-number');

        $this->assertFalse($this->validator->passed());
    }

    #[Test]
    public function it_passes_numeric_validation(): void
    {
        $this->validator->numeric('age', '25');

        $this->assertTrue($this->validator->passed());
    }

    #[Test]
    public function it_validates_minimum_value(): void
    {
        $this->validator->min('quantity', 0, 1);

        $this->assertFalse($this->validator->passed());
    }

    #[Test]
    public function it_validates_maximum_value(): void
    {
        $this->validator->max('quantity', 100, 50);

        $this->assertFalse($this->validator->passed());
    }

    #[Test]
    public function it_validates_allowed_values(): void
    {
        $this->validator->in('status', 'InvalidStatus', ['Pending', 'Confirmed', 'Shipped']);

        $this->assertFalse($this->validator->passed());
    }

    #[Test]
    public function it_passes_allowed_values_validation(): void
    {
        $this->validator->in('status', 'Pending', ['Pending', 'Confirmed', 'Shipped']);

        $this->assertTrue($this->validator->passed());
    }

    #[Test]
    public function it_chains_multiple_validations(): void
    {
        $this->validator
            ->required('email', 'user@example.com')
            ->email('email', 'user@example.com')
            ->minLength('password', 'password123', 8);

        $this->assertTrue($this->validator->passed());
    }

    #[Test]
    public function it_collects_multiple_errors(): void
    {
        $this->validator
            ->required('username', '')
            ->email('email', 'invalid')
            ->minLength('password', 'short', 8);

        $errors = $this->validator->getErrors();

        $this->assertCount(3, $errors);
        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    #[Test]
    public function it_sanitizes_string_input(): void
    {
        $input = '<script>alert("xss")</script>';
        $sanitized = ValidationHelper::sanitizeString($input);

        $this->assertStringNotContainsString('<script>', $sanitized);
    }

    #[Test]
    public function it_sanitizes_email_input(): void
    {
        $email = '  USER@EXAMPLE.COM  ';
        $sanitized = ValidationHelper::sanitizeEmail($email);

        $this->assertEquals('user@example.com', $sanitized);
    }

    #[Test]
    public function it_sanitizes_integer_input(): void
    {
        $value = '123abc';
        $sanitized = ValidationHelper::sanitizeInt($value);

        $this->assertIsInt($sanitized);
        $this->assertEquals(123, $sanitized);
    }

    #[Test]
    public function it_sanitizes_float_input(): void
    {
        $value = '123.45abc';
        $sanitized = ValidationHelper::sanitizeFloat($value);

        $this->assertIsFloat($sanitized);
        $this->assertEquals(123.45, $sanitized);
    }

    #[Test]
    public function it_validates_array_keys(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $this->validator->arrayKeys('user', $data, ['name', 'email', 'phone']);

        $this->assertFalse($this->validator->passed());
        $this->assertStringContainsString('phone', $this->validator->getErrors()['user'][0]);
    }

    #[Test]
    public function it_adds_custom_errors(): void
    {
        $this->validator->addError('custom_field', 'Custom error message');

        $this->assertFalse($this->validator->passed());
        $this->assertEquals('Custom error message', $this->validator->getErrors()['custom_field'][0]);
    }
}
