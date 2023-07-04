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
        protected int $id,
        protected string $companyName,
        protected string $contactName,
        protected string $email,
        protected string $telephone,
        protected string $street,
        protected string $houseNumber,
        protected string $postalBox,
        protected string $postalCode,
        protected string $city,
        protected string $countryCode,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    public function getPostalBox(): string
    {
        return $this->postalBox;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

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
            $this->getCompanyName(),
            $this->getStreet(),
            $this->getHouseNumber(),
            $this->getCity()
        );
    }

    public function toArray(): array
    {
        return [
            'city' => $this->getCity(),
            'companyName' => $this->getCompanyName(),
            'contactName' => $this->getContactName(),
            'country' => $this->getCountryCode(),
            'displayName' => $this->getDisplayName(),
            'email' => $this->getEmail(),
            'houseNumber' => $this->getHouseNumber(),
            'id' => $this->getId(),
            'postalBox' => $this->getPostalBox(),
            'postalCode' => $this->getPostalCode(),
            'street' => $this->getStreet(),
            'telephone' => $this->getTelephone(),
        ];
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
