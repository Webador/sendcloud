<?php

namespace JouwWeb\SendCloud\Model;

class Address
{
    public static function fromParcelData(array $data): self
    {
        return new self(
            (string)$data['name'],
            (string)$data['company_name'],
            (string)$data['address_divided']['street'],
            (string)$data['address_divided']['house_number'],
            (string)$data['city'],
            (string)$data['postal_code'],
            (string)$data['country']['iso_2'],
            (string)$data['email'],
            ((string)$data['telephone'] ?: null),
            ((string)$data['address_2'] ?: null),
            ((string)$data['to_state'] ?: null)
        );
    }

    public function __construct(
        protected string $name,
        protected ?string $companyName,
        protected string $street,
        protected string $houseNumber,
        protected string $city,
        protected string $postalCode,
        protected string $countryCode,
        protected string $emailAddress,
        protected ?string $phoneNumber = null,
        protected ?string $addressLine2 = null,
        protected ?string $countryStateCode = null
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    public function setHouseNumber(string $houseNumber): void
    {
        $this->houseNumber = $houseNumber;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): void
    {
        $this->addressLine2 = $addressLine2;
    }

    public function getCountryStateCode(): ?string
    {
        return $this->countryStateCode;
    }

    public function setCountryStateCode(?string $countryStateCode): void
    {
        $this->countryStateCode = $countryStateCode;
    }

    public function getDisplayName(): string
    {
        $displayName = $this->getName();

        if ($this->getCompanyName()) {
            $displayName .= ' / ' . $this->getCompanyName();
        }

        return $displayName;
    }

    public function toArray(): array
    {
        return [
            'city' => $this->getCity(),
            'companyName' => $this->getCompanyName(),
            'countryCode' => $this->getCountryCode(),
            'displayName' => $this->getDisplayName(),
            'emailAddress' => $this->getEmailAddress(),
            'houseNumber' => $this->getHouseNumber(),
            'name' => $this->getName(),
            'phoneNumber' => $this->getPhoneNumber(),
            'postalCode' => $this->getPostalCode(),
            'street' => $this->getStreet(),
            'countryStateCode' => $this->getCountryStateCode(),
        ];
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
