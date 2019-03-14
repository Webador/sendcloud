<?php

namespace JouwWeb\SendCloud\Model;

class SenderAddress
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $companyName;

    /** @var string */
    protected $contactName;

    /** @var string */
    protected $email;

    /** @var string */
    protected $telephone;

    /** @var string */
    protected $street;

    /** @var string */
    protected $houseNumber;

    /** @var string */
    protected $postalBox;

    /** @var string */
    protected $postalCode;

    /** @var string */
    protected $city;

    /** @var string */
    protected $countryCode;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->companyName = (string)$data['company_name'];
        $this->contactName = (string)$data['contact_name'];
        $this->email = (string)$data['email'];
        $this->telephone = (string)$data['telephone'];
        $this->street = (string)$data['street'];
        $this->houseNumber = (string)$data['house_number'];
        $this->postalBox = (string)$data['postal_box'];
        $this->postalCode = (string)$data['postal_code'];
        $this->city = (string)$data['city'];
        $this->countryCode = (string)$data['country'];
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
     * Returns a one-line description similar to how SendCloud displays a sender address.
     *
     * @return string
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
