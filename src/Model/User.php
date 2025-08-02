<?php

namespace JouwWeb\Sendcloud\Model;

class User
{
    public static function fromData(array $data): self
    {
        return new self(
            (string)$data['username'],
            (string)$data['company_name'],
            (string)$data['telephone'],
            (string)$data['address'],
            (string)$data['postal_code'],
            (string)$data['city'],
            (string)$data['email'],
            new \DateTimeImmutable((string)$data['registered']),
        );
    }

    public function __construct(
        public readonly string $username,
        public readonly string $companyName,
        public readonly string $phoneNumber,
        public readonly string $address,
        public readonly string $postalCode,
        public readonly string $city,
        public readonly string $emailAddress,
        public readonly \DateTimeImmutable $registered,
    ) {
    }

    /**
     * @deprecated Use property.
     */
    public function getUsername(): string
    {
        return $this->username;
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
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * @deprecated Use property.
     */
    public function getAddress(): string
    {
        return $this->address;
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
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    /**
     * @deprecated Use property.
     */
    public function getRegistered(): \DateTimeImmutable
    {
        return $this->registered;
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'companyName' => $this->companyName,
            'phoneNumber' => $this->phoneNumber,
            'address' => $this->address,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'emailAddress' => $this->emailAddress,
        ];
    }

    public function __toString(): string
    {
        return $this->username;
    }
}
