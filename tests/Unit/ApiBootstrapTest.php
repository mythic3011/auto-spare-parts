<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiBootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REQUEST_METHOD'] = 'get';
        unset($_SERVER['HTTP_X_REQUEST_ID']);
    }

    #[Test]
    public function it_exposes_normalized_method(): void
    {
        $this->assertSame('GET', \ApiBootstrap::method());
    }

    #[Test]
    public function it_generates_request_id_when_missing(): void
    {
        \ApiBootstrap::init();
        $this->assertNotEmpty($_SERVER['HTTP_X_REQUEST_ID'] ?? '');
    }
}
