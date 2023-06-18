<?php

namespace JouwWeb\SendCloud\Model;

class ShippingMethod
{
    protected int $id;

    protected string $name;

    protected int $minimumWeight;

    protected int $maximumWeight;

    protected string $carrier;

    /** @var array<string, int> */
    protected array $prices = [];

    protected bool $allowsServicePoints = false;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->name = (string)$data['name'];
        $this->minimumWeight = (int)($data['min_weight'] * 1000);
        $this->maximumWeight = (int)($data['max_weight'] * 1000);
        $this->carrier = (string)$data['carrier'];

        $servicePointInput = (string)$data['service_point_input'];
        $this->allowsServicePoints = ($servicePointInput !== 'none');

        foreach ((array)$data['countries'] as $country) {
            $this->prices[$country['iso_2']] = (int)($country['price'] * 100);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * In grams, inclusive.
     */
    public function getMinimumWeight(): int
    {
        return $this->minimumWeight;
    }

    /**
     * In grams, inclusive.
     */
    public function getMaximumWeight(): int
    {
        return $this->maximumWeight;
    }

    /**
     * Code of the carrier.
     */
    public function getCarrier(): string
    {
        return $this->carrier;
    }

    /**
     * Prices, in cents, indexed by country code.
     *
     * @return array<string, int>
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    public function getPriceForCountry(string $countryCode): ?int
    {
        return $this->prices[$countryCode] ?? null;
    }

    public function getAllowsServicePoints(): bool
    {
        return $this->allowsServicePoints;
    }

    public function toArray(): array
    {
        return [
            'carrier' => $this->getCarrier(),
            'id' => $this->getId(),
            'maximumWeight' => $this->getMaximumWeight(),
            'minimumWeight' => $this->getMinimumWeight(),
            'name' => $this->getName(),
            'prices' => $this->getPrices(),
        ];
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
