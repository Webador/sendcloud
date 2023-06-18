<?php

namespace JouwWeb\SendCloud\Model;

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
        protected string $username,
        protected string $companyName,
        protected string $phoneNumber,
        protected string $address,
        protected string $postalCode,
        protected string $city,
        protected string $emailAddress,
        protected \DateTimeImmutable $registered,
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getRegistered(): \DateTimeImmutable
    {
        return $this->registered;
    }

    public function toArray(): array
    {
        return [
            'username' => $this->getUsername(),
            'companyName' => $this->getCompanyName(),
            'phoneNumber' => $this->getPhoneNumber(),
            'address' => $this->getAddress(),
            'postalCode' => $this->getPostalCode(),
            'city' => $this->getCity(),
            'emailAddress' => $this->getEmailAddress(),
        ];
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }
}
