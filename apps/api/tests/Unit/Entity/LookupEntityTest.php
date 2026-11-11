<?php

declare(strict_types=1);

namespace Tests\Api\Unit\Entity;

use App\Api\Domain\Catalog\Entity\SparePartCategory;
use App\Api\Domain\Identity\Entity\Permission;
use App\Api\Domain\Identity\Entity\Role;
use App\Api\Domain\Identity\Entity\RolePermission;
use App\Api\Domain\Ordering\Entity\OrderStatus;
use PHPUnit\Framework\TestCase;

final class LookupEntityTest extends TestCase
{
    public function testLookupAndRbacEntitiesExposeFields(): void
    {
        $role = new Role('dealer', 'Dealer');
        $permission = new Permission('order.create', 'Create Order');
        $rolePermission = new RolePermission($role, $permission);
        $category = new SparePartCategory('engine', 'Engine');
        $status = new OrderStatus('pending', 'Pending');

        self::assertSame('dealer', $role->code());
        self::assertSame('Create Order', $permission->name());
        self::assertSame($role, $rolePermission->role());
        self::assertSame('engine', $category->code());
        self::assertSame('pending', $status->code());
    }
}
