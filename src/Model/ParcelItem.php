<?php

namespace JouwWeb\Sendcloud\Model;

/**
 * A product type that is contained in a parcel.
 */
class ParcelItem
{
    public static function fromData(array $data): self
    {
        return new self(
            (string)$data['description'],
            (int)$data['quantity'],
            (int)round(((float)$data['weight']) * 1000),
            (float)$data['value'],
            isset($data['hs_code']) ? (string)$data['hs_code'] : null,
            isset($data['origin_country']) ? (string)$data['origin_country'] : null,
            isset($data['sku']) ? (string)$data['sku'] : null,
            isset($data['product_id']) ? (string)$data['product_id'] : null,
            isset($data['properties']) ? (array)$data['properties'] : null,
         );
    }

    /**
     * @param string $description Description, or name, of the item.
     * @param int $weight Weight of the item in grams.
     * @param float $value The value, or price, per item. Up to 2 decimal places in accuracy.
     * @param string|null $originCountryCode 2-letter code of item production country.
     * @param string|null $productId The internal ID of the product.
     * @param array|null $properties List of properties of the product passed along as a JSON object.
     */
    public function __construct(
        public readonly string $description,
        public readonly int $quantity,
        public readonly int $weight,
        public readonly float $value,
        public readonly ?string $harmonizedSystemCode = null,
        public readonly ?string $originCountryCode = null,
        public readonly ?string $sku = null,
        public readonly ?string $productId = null,
        public readonly ?array $properties = null
    ) {
    }

    /**
     * @deprecated Use property.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @deprecated Use property.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @deprecated Use property.
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @deprecated Use property.
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @deprecated Use property.
     */
    public function getHarmonizedSystemCode(): ?string
    {
        return $this->harmonizedSystemCode;
    }

    /**
     * @deprecated Use property.
     */
    public function getOriginCountryCode(): ?string
    {
        return $this->originCountryCode;
    }

    /**
     * @deprecated Use property.
     */
    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * @deprecated Use property.
     */
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    /**
     * @deprecated Use property.
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'quantity' => $this->quantity,
            'weight' => $this->weight,
            'value' => $this->value,
            'harmonizedSystemCode' => $this->harmonizedSystemCode,
            'originCountryCode' => $this->originCountryCode,
            'sku' => $this->sku,
            'productId' => $this->productId,
            'properties' => $this->properties,
        ];
    }
}
