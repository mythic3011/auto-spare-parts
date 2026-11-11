<?php

declare(strict_types=1);

namespace App\Api\Domain\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'role_permissions')]
#[ORM\UniqueConstraint(name: 'uniq_role_permission', columns: ['role_id', 'permission_id'])]
class RolePermission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Role $role;

    #[ORM\ManyToOne(targetEntity: Permission::class)]
    #[ORM\JoinColumn(name: 'permission_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Permission $permission;

    public function __construct(Role $role, Permission $permission)
    {
        $this->role = $role;
        $this->permission = $permission;
    }

    public function id(): ?int { return $this->id; }
    public function role(): Role { return $this->role; }
    public function permission(): Permission { return $this->permission; }
}
