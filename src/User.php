<?php

namespace JouwWeb\SendCloud;

class User
{
    /** @var string */
    protected $username;

    /** @var string */
    protected $companyName;

    /** @var string */
    protected $telephone;

    /** @var string */
    protected $address;

    /** @var string */
    protected $postalCode;

    /** @var string */
    protected $city;

    /** @var string */
    protected $email;

    /** @var \DateTime */
    protected $registered;

    public function __construct(\stdClass $data)
    {
        $this->username = (string)$data->username;
        $this->companyName = (string)$data->company_name;
        $this->telephone = (string)$data->telephone;
        $this->address = (string)$data->address;
        $this->postalCode = (string)$data->postal_code;
        $this->city = (string)$data->city;
        $this->email = (string)$data->email;
        $this->registered = new \DateTime((string)$data->registered);
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRegistered(): \DateTime
    {
        return $this->registered;
    }
}
