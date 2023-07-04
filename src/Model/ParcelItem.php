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
            round(((float)$data['weight']) * 1000),
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
        protected string $description,
        protected int $quantity,
        protected int $weight,
        protected float $value,
        protected ?string $harmonizedSystemCode = null,
        protected ?string $originCountryCode = null,
        protected ?string $sku = null,
        protected ?string $productId = null,
        protected ?array $properties = null
    ) {
        $this->value = round($value, 2);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getHarmonizedSystemCode(): ?string
    {
        return $this->harmonizedSystemCode;
    }

    public function getOriginCountryCode(): ?string
    {
        return $this->originCountryCode;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->getDescription(),
            'quantity' => $this->getQuantity(),
            'weight' => $this->getWeight(),
            'value' => $this->getValue(),
            'harmonizedSystemCode' => $this->getHarmonizedSystemCode(),
            'originCountryCode' => $this->getOriginCountryCode(),
            'sku' => $this->getSku(),
            'productId' => $this->getProductId(),
            'properties' => $this->getProperties(),
        ];
    }
}
