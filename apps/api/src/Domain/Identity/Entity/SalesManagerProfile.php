<?php

declare(strict_types=1);

namespace App\Api\Domain\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sales_manager_profiles')]
class SalesManagerProfile
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'salesManagerProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'manager_name', type: 'string', length: 100)]
    private string $managerName;

    #[ORM\Column(name: 'contact_name', type: 'string', length: 100)]
    private string $contactName;

    #[ORM\Column(name: 'contact_number', type: 'string', length: 32)]
    private string $contactNumber;

    public function __construct(User $user, string $managerName, string $contactName, string $contactNumber)
    {
        $this->user = $user;
        $this->managerName = trim($managerName);
        $this->contactName = trim($contactName);
        $this->contactNumber = trim($contactNumber);

        $this->user->assignSalesManagerProfile($this);
    }

    public function user(): User { return $this->user; }
    public function managerName(): string { return $this->managerName; }
}
