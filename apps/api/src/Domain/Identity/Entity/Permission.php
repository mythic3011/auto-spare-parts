<?php

declare(strict_types=1);

namespace App\Api\Domain\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'permissions')]
#[ORM\UniqueConstraint(name: 'uniq_permissions_code', columns: ['code'])]
#[ORM\UniqueConstraint(name: 'uniq_permissions_name', columns: ['name'])]
class Permission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $code;

    #[ORM\Column(type: 'string', length: 128)]
    private string $name;

    public function __construct(string $code, string $name)
    {
        $this->code = trim($code);
        $this->name = trim($name);
    }

    public function id(): ?int { return $this->id; }
    public function code(): string { return $this->code; }
    public function name(): string { return $this->name; }
}
