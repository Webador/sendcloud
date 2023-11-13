<?php

namespace JouwWeb\Sendcloud\Model;

/**
 * Implementation of sencloud service point object
 * @see https://api.sendcloud.dev/docs/sendcloud-public-api/service-points%2Foperations%2Flist-service-points
 */
class ServicePoint
{
    public static function fromData(array $data) : self
    {
        return new self(
            (int) $data['id'],
            (string) $data['code'],
            (bool) $data['is_active'],
            isset($data['shop_type']) ? (string) $data['shop_type'] : null,
            (array) $data['extra_data'],
            (string) $data['name'],
            (string) $data['street'],
            (string) $data['house_number'],
            (string) $data['postal_code'],
            (string) $data['city'],
            (string) $data['latitude'],
            (string) $data['longitude'],
            (string) $data['email'],
            (string) $data['phone'],
            (string) $data['homepage'],
            (string) $data['carrier'],
            (string) $data['country'],
            (array) $data['formatted_opening_times'],
            (bool) $data['open_tomorrow'],
            (bool) $data['open_upcoming_week'],
            (int) $data['distance']
        );
    }

    /**
     * @param array<string, string> $extra_data Can contain carrier specific data
     * @param array<int, array<string>> $formatted_opening_times
     * @param int $distance Distance between the reference point and the service point in meters.
     */
    public function __construct(
        protected int $id,
        protected string $code,
        protected bool $is_active,
        protected ?string $shop_type = null,
        protected array $extra_data,
        protected string $name,
        protected string $street,
        protected string $house_number,
        protected string $postal_code,
        protected string $city,
        protected string $latitude,
        protected string $longitude,
        protected string $email,
        protected string $phone,
        protected string $homepage,
        protected string $carrier,
        protected string $country,
        protected array $formatted_opening_times,
        protected bool $open_tomorrow,
        protected bool $open_upcoming_week,
        protected int $distance
    ) {

    }

    /** Getters */

    public function getId() : int
    {
        return $this->id;
    }

    public function getCode() : string
    {
        return $this->code;
    }

    public function isActive() : bool
    {
        return $this->is_active;
    }

    public function getShopType() : ?string
    {
        return $this->shop_type;
    }

    /**
     * Can contain carrier specific data
     * @return array<string, string>
     */
    public function getExtraData() : array
    {
        return $this->extra_data;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getStreet() : string
    {        
        return $this->street;
    }

    public function getHouseNumber() : string
    {
        return $this->house_number;
    }

    public function getPostalCode() : string
    {
        return $this->postal_code;
    }

    public function getCity() : string
    {
        return $this->city;
    }

    public function getLatitude() : string
    {
        return $this->latitude;
    }

    public function getLongitude() : string
    {
        return $this->longitude;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function getPhone() : string
    {
        return $this->phone;
    }

    public function getHomepage() : string
    {
        return $this->homepage;
    }

    public function getCarrier() : string
    {
        return $this->carrier;
    }

    public function getCountry() : string
    {
        return $this->country;
    }

    /**
     * @return array<int, array<string>>
     */
    public function getFormattedOpeningTimes() : array
    {
        return $this->formatted_opening_times;
    }

    public function isOpenTomorrow() : bool
    {
        return $this->open_tomorrow;
    }

    public function isOpenUpcomingWeek() : bool
    {
        return $this->open_upcoming_week;
    }

    /**
     * Distance between the reference point and the service point in meters.
     * @return int
     */
    public function getDistance() : int
    {
        return $this->distance;
    }
}