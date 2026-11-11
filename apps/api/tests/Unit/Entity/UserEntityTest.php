<?php

declare(strict_types=1);

namespace Tests\Api\Unit\Entity;

use App\Api\Domain\Common\ValueObject\Address;
use App\Api\Domain\Common\ValueObject\PublicId;
use App\Api\Domain\Identity\Entity\DealerProfile;
use App\Api\Domain\Identity\Entity\Role;
use App\Api\Domain\Identity\Entity\SalesManagerProfile;
use App\Api\Domain\Identity\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserEntityTest extends TestCase
{
    public function testUserProfileAssignmentAndPublicId(): void
    {
        $dealerRole = new Role('dealer', 'Dealer');
        $dealerUserId = PublicId::generate('usr');
        $dealer = new User((string) $dealerUserId, 'Dealer@Example.com', 'hash', $dealerRole);

        $address = new Address('Flat A', null, 'Sha Tin', 'Hong Kong', null, 'hk');
        $dealerProfile = new DealerProfile(
            $dealer,
            'Acme Auto',
            'Chan Tai Man',
            '91234567',
            null,
            $address
        );

        self::assertSame('dealer@example.com', $dealer->email());
        self::assertSame($dealerProfile, $dealer->dealerProfile());
        self::assertSame('HK', $dealerProfile->deliveryAddress()->countryCode());

        $managerRole = new Role('sales_manager', 'Sales Manager');
        $manager = new User((string) PublicId::generate('usr'), 'staff@example.com', 'hash2', $managerRole);
        $managerProfile = new SalesManagerProfile($manager, 'Alex Lee', 'Alex Lee', '95556666');

        self::assertSame($managerProfile, $manager->salesManagerProfile());
        self::assertNull($manager->dealerProfile());

        $parsed = PublicId::fromString((string) $dealerUserId, 'usr');
        self::assertSame((string) $dealerUserId, $parsed->value());
    }
}
