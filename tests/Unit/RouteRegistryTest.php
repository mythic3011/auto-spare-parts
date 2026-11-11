<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RouteRegistryTest extends TestCase
{
    #[Test]
    public function it_resolves_aliases_and_dispatches_handler(): void
    {
        $registry = new \RouteRegistry();
        $registry->add('orders.dealer_list', ['GET'], static fn (): array => ['ok' => true]);
        $registry->alias('dealer-list', 'orders.dealer_list');

        $result = $registry->dispatch('dealer-list', 'GET');
        $this->assertSame(['ok' => true], $result);
    }

    #[Test]
    public function it_throws_404_for_unknown_route(): void
    {
        $registry = new \RouteRegistry();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route not found');
        $registry->dispatch('missing-route', 'GET');
    }

    #[Test]
    public function it_throws_405_for_disallowed_method(): void
    {
        $registry = new \RouteRegistry();
        $registry->add('profile.get', ['GET'], static fn (): null => null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method not allowed');
        $registry->dispatch('profile.get', 'POST');
    }
}
