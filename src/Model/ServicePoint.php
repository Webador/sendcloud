<?php

namespace JouwWeb\Sendcloud\Model;

/**
 * Implementation of Sendcloud service point object.
 *
 * @see https://api.sendcloud.dev/docs/sendcloud-public-api/service-points%2Foperations%2Fget-a-service-point
 */
class ServicePoint
{
    public static function fromData(array $data): self
    {
        return new self(
            (int)$data['id'],
            (string)$data['code'],
            (bool)$data['is_active'],
            isset($data['shop_type']) ? (string)$data['shop_type'] : null,
            (array)$data['extra_data'],
            (string)$data['name'],
            (string)$data['street'],
            (string)$data['house_number'],
            (string)$data['postal_code'],
            (string)$data['city'],
            (string)$data['latitude'],
            (string)$data['longitude'],
            (string)$data['email'],
            (string)$data['phone'],
            (string)$data['homepage'],
            (string)$data['carrier'],
            (string)$data['country'],
            (array)$data['formatted_opening_times'],
            (bool)$data['open_tomorrow'],
            (bool)$data['open_upcoming_week'],
            isset($data['distance']) ? (int) $data['distance'] : null
        );
    }

    /**
     * @param array<string, string> $extraData Can contain carrier specific data
     * @param array<int, string[]> $formattedOpeningTimes
     * @param ?int $distance Distance in meters OR null if latitude and longitude are not provided in the request
     */
    public function __construct(
        protected int $id,
        protected string $code,
        protected bool $isActive,
        protected ?string $shopType,
        protected array $extraData,
        protected string $name,
        protected string $street,
        protected string $houseNumber,
        protected string $postalCode,
        protected string $city,
        protected string $latitude,
        protected string $longitude,
        protected string $email,
        protected string $phone,
        protected string $homepage,
        protected string $carrier,
        protected string $country,
        protected array $formattedOpeningTimes,
        protected bool $openTomorrow,
        protected bool $openUpcomingWeek,
        protected ?int $distance,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getShopType(): ?string
    {
        return $this->shopType;
    }

    /**
     * Can contain carrier specific data.
     *
     * @return array<string, string>
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getHomepage(): string
    {
        return $this->homepage;
    }

    public function getCarrier(): string
    {
        return $this->carrier;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return array<int, string[]>
     */
    public function getFormattedOpeningTimes(): array
    {
        return $this->formattedOpeningTimes;
    }

    public function isOpenTomorrow(): bool
    {
        return $this->openTomorrow;
    }

    public function isOpenUpcomingWeek(): bool
    {
        return $this->openUpcomingWeek;
    }

    /**
     * @return ?int Distance in meters OR null if latitude and longitude are not provided in the request
     */
    public function getDistance(): ?int
    {
        return $this->distance;
    }
}
