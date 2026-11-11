<?php

declare(strict_types=1);

namespace App\Api\Domain\Common\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final class Address
{
    #[ORM\Column(name: 'line_1', type: 'string', length: 120)]
    private string $line1;

    #[ORM\Column(name: 'line_2', type: 'string', length: 120, nullable: true)]
    private ?string $line2;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $district;

    #[ORM\Column(type: 'string', length: 120)]
    private string $city;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $region;

    #[ORM\Column(name: 'country_code', type: 'string', length: 2)]
    private string $countryCode;

    public function __construct(
        string $line1,
        ?string $line2,
        ?string $district,
        string $city,
        ?string $region,
        string $countryCode
    ) {
        $line1 = trim($line1);
        $city = trim($city);
        $countryCode = strtoupper(trim($countryCode));

        if ($line1 == '' || $city == '') {
            throw new InvalidArgumentException('Address line1 and city are required.');
        }

        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new InvalidArgumentException('Country code must be ISO 3166-1 alpha-2.');
        }

        $this->line1 = $line1;
        $this->line2 = $line2 !== null ? trim($line2) : null;
        $this->district = $district !== null ? trim($district) : null;
        $this->city = $city;
        $this->region = $region !== null ? trim($region) : null;
        $this->countryCode = $countryCode;
    }

    public function line1(): string { return $this->line1; }
    public function line2(): ?string { return $this->line2; }
    public function district(): ?string { return $this->district; }
    public function city(): string { return $this->city; }
    public function region(): ?string { return $this->region; }
    public function countryCode(): string { return $this->countryCode; }
}
