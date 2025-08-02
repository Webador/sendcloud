<?php

namespace JouwWeb\Sendcloud\Model;

class Address
{
    public static function fromParcelData(array $data): self
    {
        return new self(
            name: (string)$data['name'],
            companyName: (string)$data['company_name'],
            addressLine1: (string)$data['address'],
            city: (string)$data['city'],
            postalCode: (string)$data['postal_code'],
            countryCode: (string)$data['country']['iso_2'],
            emailAddress: (string)$data['email'],
            houseNumber: (string)$data['address_divided']['house_number'],
            phoneNumber: ((string)$data['telephone'] ?: null),
            addressLine2: ((string)$data['address_2'] ?: null),
            countryStateCode: ((string)$data['to_state'] ?: null),
            street: (string)$data['address_divided']['street'],
        );
    }

    /**
     * @param string $addressLine1 Full address line 1. Includes house number unless explicitly specifying {@see $houseNumber}.
     * @param string|null $houseNumber Will be added onto {@see $addressLine1}. Leave out if {@see $addressLine1} already contains a house number.
     * @param string|null $street Street parsed from address by Sendcloud.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $companyName,
        public readonly string $addressLine1,
        public readonly string $city,
        public readonly string $postalCode,
        public readonly string $countryCode,
        public readonly string $emailAddress,
        public readonly ?string $houseNumber = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $addressLine2 = null,
        public readonly ?string $countryStateCode = null,
        public readonly ?string $street = null,
    ) {
    }

    /**
     * @deprecated Use property.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @deprecated Use property.
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @deprecated Use property.
     */
    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    /**
     * @deprecated Use property.
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @deprecated Use property.
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * @deprecated Use property.
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * @deprecated Use property.
     */
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    /**
     * @deprecated Use property.
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @deprecated Use property.
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * @deprecated Use property.
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @deprecated Use property.
     */
    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    /**
     * @deprecated Use property.
     */
    public function getCountryStateCode(): ?string
    {
        return $this->countryStateCode;
    }

    public function getDisplayName(): string
    {
        $displayName = $this->name;

        if ($this->companyName) {
            $displayName .= ' / ' . $this->companyName;
        }

        return $displayName;
    }

    public function toArray(): array
    {
        return [
            'city' => $this->city,
            'companyName' => $this->companyName,
            'countryCode' => $this->countryCode,
            'displayName' => $this->getDisplayName(),
            'emailAddress' => $this->emailAddress,
            'name' => $this->name,
            'phoneNumber' => $this->phoneNumber,
            'postalCode' => $this->postalCode,
            'address' => $this->addressLine1,
            'street' => $this->street,
            'houseNumber' => $this->houseNumber,
            'countryStateCode' => $this->countryStateCode,
        ];
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
