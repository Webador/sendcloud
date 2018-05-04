<?php

namespace Villermen\SendCloud;

class ShippingMethod
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $name;

    /** @var int */
    protected $minimumWeight;

    /** @var int */
    protected $maximumWeight;

    /** @var string */
    protected $carrier;

    /** @var float[] */
    protected $prices = [];

    public function __construct(\stdClass $data)
    {
        $this->id = (int)$data->id;
        $this->name = (string)$data->name;
        $this->minimumWeight = (int)($data->min_weight * 1000);
        $this->maximumWeight = (int)($data->max_weight * 1000);
        $this->carrier = (string)$data->carrier;

        foreach ((array)$data->countries as $country) {
            $this->prices[$country->iso_2] = (float)$country->price;
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
     *
     * @return int
     */
    public function getMinimumWeight(): int
    {
        return $this->minimumWeight;
    }

    /**
     * In grams, inclusive.
     *
     * @return int
     */
    public function getMaximumWeight(): int
    {
        return $this->maximumWeight;
    }

    /**
     * Code of the carrier.
     *
     * @return string
     */
    public function getCarrier(): string
    {
        return $this->carrier;
    }

    /**
     * Prices, indexed by country code.
     *
     * @return float[]
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    public function getPriceForCountry(string $countryCode): ?float
    {
        return $this->prices[$countryCode] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'minimumWeight' => $this->getMinimumWeight(),
            'maximumWeight' => $this->getMaximumWeight(),
            'carrier' => $this->getCarrier(),
            'prices' => $this->getPrices(),
        ];
    }
}
