<?php

declare(strict_types=1);

namespace App\Api\Domain\Identity\Entity;

use App\Api\Domain\Common\ValueObject\Address;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'dealer_profiles')]
class DealerProfile
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'dealerProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'company_name', type: 'string', length: 100)]
    private string $companyName;

    #[ORM\Column(name: 'contact_name', type: 'string', length: 100)]
    private string $contactName;

    #[ORM\Column(name: 'contact_number', type: 'string', length: 32)]
    private string $contactNumber;

    #[ORM\Column(name: 'fax_number', type: 'string', length: 32, nullable: true)]
    private ?string $faxNumber;

    #[ORM\Embedded(class: Address::class, columnPrefix: 'delivery_')]
    private Address $deliveryAddress;

    public function __construct(
        User $user,
        string $companyName,
        string $contactName,
        string $contactNumber,
        ?string $faxNumber,
        Address $deliveryAddress
    ) {
        $this->user = $user;
        $this->companyName = trim($companyName);
        $this->contactName = trim($contactName);
        $this->contactNumber = trim($contactNumber);
        $this->faxNumber = $faxNumber !== null ? trim($faxNumber) : null;
        $this->deliveryAddress = $deliveryAddress;

        $this->user->assignDealerProfile($this);
    }

    public function user(): User { return $this->user; }
    public function companyName(): string { return $this->companyName; }
    public function deliveryAddress(): Address { return $this->deliveryAddress; }
}
