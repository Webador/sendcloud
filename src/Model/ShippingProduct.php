<?php

namespace JouwWeb\Sendcloud\Model;

class ShippingProduct
{
    public const DELIVERY_MODE_HOME_DELIVERY = 'home_delivery';
    public const DELIVERY_MODE_MAILBOX = 'mailbox';
    public const DELIVERY_MODE_POBOX = 'pobox';
    public const DELIVERY_MODE_SERVICE_POINT = 'service_point';
    public const DELIVERY_MODES = [
        self::DELIVERY_MODE_HOME_DELIVERY,
        self::DELIVERY_MODE_MAILBOX,
        self::DELIVERY_MODE_POBOX,
        self::DELIVERY_MODE_SERVICE_POINT,
    ];

    public const WEIGHT_UNIT_GRAM = 'gram';
    public const WEIGHT_UNIT_KILOGRAM = 'kilogram';
    public const WEIGHT_UNITS = [
        self::WEIGHT_UNIT_GRAM,
        self::WEIGHT_UNIT_KILOGRAM,
    ];

    public static function fromData(mixed $data): self
    {
        $allowsServicePoints = in_array(
            ShippingProduct::DELIVERY_MODE_SERVICE_POINT,
            $data['available_functionalities']['last_mile'] ?? [],
            true
        );
        $withReturn = in_array(
            true,
            $data['available_functionalities']['returns'] ?? []
        );


        $carrier = (string)$data['carrier'];

        $shippingMethods = [];

        forEach($data['methods'] as $shippingMethodData) {
            // "min_weight" and "max_weight" values
            // of shippingMethod from shippingProduct are directly get in grams
            $minimumWeight = (int)$shippingMethodData['properties']['min_weight'] ?? null;
            $maximumWeight = (int)$shippingMethodData['properties']['max_weight'] ?? null;

            $shippingMethods[] = new ShippingMethod(
                (int)$shippingMethodData['id'],
                (string)$shippingMethodData['name'],
                $minimumWeight,
                $maximumWeight,
                $carrier,
                [],
                $allowsServicePoints,
            );
        }

        usort($shippingMethods, ShippingMethod::compareByCarrierAndName(...));

        return new self(
            (string)$data['name'],
            (string)$data['carrier'],
            (int)$data['weight_range']['min_weight'] ?? null,
            (int)$data['weight_range']['max_weight'] ?? null,
            $withReturn,
            $allowsServicePoints,
            $shippingMethods,
        );
    }

    /**
     * @param string $name
     * @param string $carrier Code of the carrier.
     * @param int $minimumWeight In grams, inclusive.
     * @param int $maximumWeight In grams, inclusive.
     * @param bool $allowsServicePoints In grams, inclusive.
     * @param ShippingMethod[] $methods Shipping methods related to this shipping product.
     * @param bool $withReturn When true, this shipping product can be used for making a return shipment.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $carrier,
        public readonly int $minimumWeight,
        public readonly int $maximumWeight,
        public readonly bool $withReturn,
        public readonly bool $allowsServicePoints = false,
        public readonly array $methods = [],
    ) {
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
    public function getCarrier(): string
    {
        return $this->carrier;
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
    public function getWithReturn(): string
    {
        return $this->withReturn;
    }

    /**
     * @deprecated Use property.
     */
    public function getAllowServicePoints(): bool
    {
        return $this->allowsServicePoints;
    }

    /**
     * @deprecated Use property.
     */
    public function getMethods(): array
    {
        return $this->methods;
    }
}
