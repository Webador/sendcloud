<?php

namespace JouwWeb\SendCloud\Model;

/**
 * A product type that is contained in a parcel.
 */
class ParcelItem
{
    /** @var string Description, or name, of the item. */
    private $description;

    /** @var int */
    private $quantity;

    /** @var int In grams. */
    private $weight;

    /** @var float The value, or price, per item. Up to 2 decimal places in accuracy. */
    private $value;

    /** @var string|null */
    private $harmonizedSystemCode;

    /** @var string|null 2 letter code of item production country. */
    private $originCountryCode;

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

        return $item;
    }

    public function __construct(
        string $description,
        int $quantity,
        int $weight,
        float $value,
        ?string $harmonizedSystemCode = null,
        ?string $originCountryCode = null
    ) {
        $this->setDescription($description);
        $this->setQuantity($quantity);
        $this->setWeight($weight);
        $this->setValue($value);
        $this->setHarmonizedSystemCode($harmonizedSystemCode);
        $this->setOriginCountryCode($originCountryCode);
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

    public function toArray(): array
    {
        return [
            'description' => $this->getDescription(),
            'quantity' => $this->getQuantity(),
            'weight' => $this->getWeight(),
            'value' => $this->getValue(),
            'harmonizedSystemCode' => $this->getHarmonizedSystemCode(),
            'originCountryCode' => $this->getOriginCountryCode(),
        ];
    }
}
