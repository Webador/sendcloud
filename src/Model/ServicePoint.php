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
     * @param int|null $distance Distance in meters OR null if latitude and longitude are not provided in the request
     */
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly bool $isActive,
        public readonly ?string $shopType,
        public readonly array $extraData,
        public readonly string $name,
        public readonly string $street,
        public readonly string $houseNumber,
        public readonly string $postalCode,
        public readonly string $city,
        public readonly string $latitude,
        public readonly string $longitude,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $homepage,
        public readonly string $carrier,
        public readonly string $country,
        public readonly array $formattedOpeningTimes,
        public readonly bool $openTomorrow,
        public readonly bool $openUpcomingWeek,
        public readonly ?int $distance,
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
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @deprecated Use property.
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @deprecated Use property.
     */
    public function getShopType(): ?string
    {
        return $this->shopType;
    }

    /**
     * Can contain carrier specific data.
     *
     * @return array<string, string>
     * @deprecated Use property.
     */
    public function getExtraData(): array
    {
        return $this->extraData;
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
    public function getLatitude(): string
    {
        return $this->latitude;
    }

    /**
     * @deprecated Use property.
     */
    public function getLongitude(): string
    {
        return $this->longitude;
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
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @deprecated Use property.
     */
    public function getHomepage(): string
    {
        return $this->homepage;
    }

    /**
     * @deprecated Use property.
     */
    public function getCarrier(): string
    {
        return $this->carrier;
    }

    /**
     * @deprecated Use property.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return array<int, string[]>
     * @deprecated Use property.
     */
    public function getFormattedOpeningTimes(): array
    {
        return $this->formattedOpeningTimes;
    }

    /**
     * @deprecated Use property.
     */
    public function isOpenTomorrow(): bool
    {
        return $this->openTomorrow;
    }

    /**
     * @deprecated Use property.
     */
    public function isOpenUpcomingWeek(): bool
    {
        return $this->openUpcomingWeek;
    }

    /**
     * @return ?int Distance in meters OR null if latitude and longitude are not provided in the request
     * @deprecated Use property.
     */
    public function getDistance(): ?int
    {
        return $this->distance;
    }
}
