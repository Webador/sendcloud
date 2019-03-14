<?php

namespace JouwWeb\SendCloud\Model;

class User
{
    /** @var string */
    protected $username;

    /** @var string */
    protected $companyName;

    /** @var string */
    protected $phoneNumber;

    /** @var string */
    protected $address;

    /** @var string */
    protected $postalCode;

    /** @var string */
    protected $city;

    /** @var string */
    protected $emailAddress;

    /** @var \DateTime */
    protected $registered;

    public function __construct(array $data)
    {
        $this->username = (string)$data['username'];
        $this->companyName = (string)$data['company_name'];
        $this->phoneNumber = (string)$data['telephone'];
        $this->address = (string)$data['address'];
        $this->postalCode = (string)$data['postal_code'];
        $this->city = (string)$data['city'];
        $this->emailAddress = (string)$data['email'];
        $this->registered = new \DateTimeImmutable((string)$data['registered']);
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
