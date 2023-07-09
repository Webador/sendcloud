<?php

namespace JouwWeb\Sendcloud\Model;

class Address
{
    /** Street parsed from address by Sendcloud. */
    protected ?string $street = null;

    public static function fromParcelData(array $data): self
    {
        $address = new self(
            (string)$data['name'],
            (string)$data['company_name'],
            (string)$data['address'],
            (string)$data['city'],
            ((string)$data['postal_code'] ?: null),
            (string)$data['country']['iso_2'],
            (string)$data['email'],
            (string)$data['address_divided']['house_number'],
            ((string)$data['telephone'] ?: null),
            ((string)$data['address_2'] ?: null),
            ((string)$data['to_state'] ?: null)
        );

        $address->street = (string)$data['address_divided']['street'];

        return $address;
    }

    /**
     * @param string $addressLine1 Full address line 1. Includes house number unless explicitly specifying {@see $houseNumber}.
     * @param string|null $houseNumber Will be added onto {@see $addressLine1}. Leave out if {@see $addressLine1} already contains a house number.
     */
    public function __construct(
        protected string $name,
        protected ?string $companyName,
        protected string $addressLine1,
        protected string $city,
        protected ?string $postalCode,
        protected string $countryCode,
        protected string $emailAddress,
        protected ?string $houseNumber = null,
        protected ?string $phoneNumber = null,
        protected ?string $addressLine2 = null,
        protected ?string $countryStateCode = null
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function getCountryStateCode(): ?string
    {
        return $this->countryStateCode;
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
            'name' => $this->getName(),
            'phoneNumber' => $this->getPhoneNumber(),
            'postalCode' => $this->getPostalCode(),
            'address' => $this->getAddressLine1(),
            'street' => $this->getStreet(),
            'houseNumber' => $this->getHouseNumber(),
            'countryStateCode' => $this->getCountryStateCode(),
        ];
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
