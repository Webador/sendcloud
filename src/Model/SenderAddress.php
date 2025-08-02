<?php

namespace JouwWeb\Sendcloud\Model;

class SenderAddress
{
    public static function fromData(array $data): self
    {
        return new self(
            (int)$data['id'],
            (string)$data['company_name'],
            (string)$data['contact_name'],
            (string)$data['email'],
            (string)$data['telephone'],
            (string)$data['street'],
            (string)$data['house_number'],
            (string)$data['postal_box'],
            (string)$data['postal_code'],
            (string)$data['city'],
            (string)$data['country'],
        );
    }

    public function __construct(
        public readonly int $id,
        public readonly string $companyName,
        public readonly string $contactName,
        public readonly string $email,
        public readonly string $telephone,
        public readonly string $street,
        public readonly string $houseNumber,
        public readonly string $postalBox,
        public readonly string $postalCode,
        public readonly string $city,
        public readonly string $countryCode,
    ) {
    }

    /**
     * @deprecated Use property.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @deprecated Use property.
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * @deprecated Use property.
     */
    public function getContactName(): string
    {
        return $this->contactName;
    }

    /**
     * @deprecated Use property.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @deprecated Use property.
     */
    public function getTelephone(): string
    {
        return $this->telephone;
    }

    /**
     * @deprecated Use property.
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @deprecated Use property.
     */
    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    /**
     * @deprecated Use property.
     */
    public function getPostalBox(): string
    {
        return $this->postalBox;
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
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @deprecated Use property.
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Returns a one-line description similar to how Sendcloud displays a sender address.
     */
    public function getDisplayName(): string
    {
        return sprintf(
            '%s, %s %s, %s',
            $this->companyName,
            $this->street,
            $this->houseNumber,
            $this->city,
        );
    }

    public function toArray(): array
    {
        return [
            'city' => $this->city,
            'companyName' => $this->companyName,
            'contactName' => $this->contactName,
            'country' => $this->countryCode,
            'displayName' => $this->getDisplayName(),
            'email' => $this->email,
            'houseNumber' => $this->houseNumber,
            'id' => $this->id,
            'postalBox' => $this->postalBox,
            'postalCode' => $this->postalCode,
            'street' => $this->street,
            'telephone' => $this->telephone,
        ];
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
