<?php

declare(strict_types=1);

namespace App\Api\Domain\Ordering\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_statuses')]
#[ORM\UniqueConstraint(name: 'uniq_order_statuses_code', columns: ['code'])]
#[ORM\UniqueConstraint(name: 'uniq_order_statuses_name', columns: ['name'])]
class OrderStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'smallint')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 32)]
    private string $code;

    #[ORM\Column(type: 'string', length: 64)]
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
