<?php

namespace JouwWeb\SendCloud\Model;

/**
 * A product type that is contained in a parcel.
 */
class ParcelItem
{
    /** Description, or name, of the item. */
    private string $description;

    private int $quantity;

    /** Weight of the item in grams. */
    private int $weight;

    /** The value, or price, per item. Up to 2 decimal places in accuracy. */
    private float $value;

    private ?string $harmonizedSystemCode = null;

    /** 2-letter code of item production country. */
    private ?string $originCountryCode = null;

    private ?string $sku = null;

    /** The internal ID of the product. */
    private ?string $productId = null;

    /** List of properties of the product passed along as a JSON object. */
    private ?array $properties = null;

    public static function createFromData(array $data): self
    {
        $item = new self(
            (string)$data['description'],
            (int)$data['quantity'],
            round(((float)$data['weight']) * 1000),
            (float)$data['value']
        );

        if (isset($data['hs_code'])) {
            $item->setHarmonizedSystemCode((string)$data['hs_code']);
        }
        if (isset($data['origin_country'])) {
            $item->setOriginCountryCode((string)$data['origin_country']);
        }
        if (isset($data['sku'])) {
            $item->setSku((string)$data['sku']);
        }
        if (isset($data['product_id'])) {
            $item->setProductId((string)$data['product_id']);
        }
        if (isset($data['properties'])) {
            $item->setProperties((array)$data['properties']);
        }

        return $item;
    }

    public function __construct(
        string $description,
        int $quantity,
        int $weight,
        float $value,
        ?string $harmonizedSystemCode = null,
        ?string $originCountryCode = null,
        ?string $sku = null,
        ?string $productId = null,
        ?array $properties = null
    ) {
        $this->setDescription($description);
        $this->setQuantity($quantity);
        $this->setWeight($weight);
        $this->setValue($value);
        $this->setHarmonizedSystemCode($harmonizedSystemCode);
        $this->setOriginCountryCode($originCountryCode);
        $this->setSku($sku);
        $this->setProductId($productId);
        $this->setProperties($properties);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): void
    {
        $this->value = round($value, 2);
    }

    public function getHarmonizedSystemCode(): ?string
    {
        return $this->harmonizedSystemCode;
    }

    public function setHarmonizedSystemCode(?string $harmonizedSystemCode): void
    {
        $this->harmonizedSystemCode = $harmonizedSystemCode;
    }

    public function getOriginCountryCode(): ?string
    {
        return $this->originCountryCode;
    }

    public function setOriginCountryCode(?string $originCountryCode): void
    {
        $this->originCountryCode = $originCountryCode;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): void
    {
        $this->sku = $sku;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function setProperties(?array $properties): void
    {
        $this->properties = $properties;
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
