<?php

namespace JouwWeb\Sendcloud\Model;

class ShippingMethod
{
    public static function fromData(array $data): self
    {
        $prices = [];
        foreach ((array)$data['countries'] as $country) {
            $prices[$country['iso_2']] = (int)($country['price'] * 100);
        }

        return new self(
            (int)$data['id'],
            (string)$data['name'],
            (int)round($data['min_weight'] * 1000.0),
            (int)round($data['max_weight'] * 1000.0),
            (string)$data['carrier'],
            $prices,
            $data['service_point_input'] !== 'none',
        );
    }

    public static function fromShippingProductData(array $shippingProductData): array
    {
        $allowsServicePoints = in_array(
            ShippingProduct::DELIVERY_MODE_SERVICE_POINT,
            $shippingProductData['available_functionalities']['last_mile'] ?? [],
            true
        );
        $carrier = (string)$shippingProductData['carrier'];

        $shippingMethods = [];

        forEach($shippingProductData['methods'] as $shippingMethodData) {
            $shippingMethods[] = new self(
                (int)$shippingMethodData['id'],
                (string)$shippingMethodData['name'],
                // "min_weight" and "max_weight" values
                // of shippingMethod from shippingProduct are directly get in grams
                (int)$shippingMethodData['properties']['min_weight'] ?? null,
                (int)$shippingMethodData['properties']['max_weight'] ?? null,
                $carrier,
                [],
                $allowsServicePoints,
            );
        }

        return $shippingMethods;
    }

    /**
     * @param int $id
     * @param string $name
     * @param int $minimumWeight In grams, inclusive.
     * @param int $maximumWeight In grams, inclusive.
     * @param string $carrier Code of the carrier.
     * @param array<string, int> $prices
     * @param bool $allowsServicePoints
     */
    public function __construct(
        protected int $id,
        protected string $name,
        protected int $minimumWeight,
        protected int $maximumWeight,
        protected string $carrier,
        protected array $prices = [],
        protected bool $allowsServicePoints = false,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMinimumWeight(): int
    {
        return $this->minimumWeight;
    }

    public function getMaximumWeight(): int
    {
        return $this->maximumWeight;
    }

    public function getCarrier(): string
    {
        return $this->carrier;
    }

    /**
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

    public static function compareByCarrierAndName(ShippingMethod $method1, ShippingMethod $method2): int
    {
        if ($method1->getCarrier() !== $method2->getCarrier()) {
            return strcasecmp($method1->getCarrier(), $method2->getCarrier());
        }

        return strcasecmp($method1->getName(), $method2->getName());
    }
}
