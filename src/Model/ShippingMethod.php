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
        public readonly int $id,
        public readonly string $name,
        public readonly int $minimumWeight,
        public readonly int $maximumWeight,
        public readonly string $carrier,
        public readonly array $prices = [],
        public readonly bool $allowsServicePoints = false,
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @deprecated Use property.
     */
    public function getMinimumWeight(): int
    {
        return $this->minimumWeight;
    }

    /**
     * @deprecated Use property.
     */
    public function getMaximumWeight(): int
    {
        return $this->maximumWeight;
    }

    /**
     * @deprecated Use property.
     */
    public function getCarrier(): string
    {
        return $this->carrier;
    }

    /**
     * @return array<string, int>
     * @deprecated Use property.
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    /**
     * @deprecated Use property.
     */
    public function getPriceForCountry(string $countryCode): ?int
    {
        return $this->prices[$countryCode] ?? null;
    }

    /**
     * @deprecated Use property.
     */
    public function getAllowsServicePoints(): bool
    {
        return $this->allowsServicePoints;
    }

    public function toArray(): array
    {
        return [
            'carrier' => $this->carrier,
            'id' => $this->id,
            'maximumWeight' => $this->maximumWeight,
            'minimumWeight' => $this->minimumWeight,
            'name' => $this->name,
            'prices' => $this->prices,
        ];
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public static function compareByCarrierAndName(ShippingMethod $method1, ShippingMethod $method2): int
    {
        if ($method1->carrier !== $method2->carrier) {
            return strcasecmp($method1->carrier, $method2->carrier);
        }

        return strcasecmp($method1->name, $method2->name);
    }
}
