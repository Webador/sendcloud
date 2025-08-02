<?php

namespace JouwWeb\Sendcloud\Model;

/**
 * Dimensions of a parcel used for volumetric weight calculation and passed on
 * to carriers.
 */
class ParcelDimensions
{
    /**
     * @param float $length In centimeters, up to 2 decimal places.
     * @param float $width In centimeters up to 2 decimal places.
     * @param float $height In centimeters, up to 2 decimal places.
     */
    public function __construct(
        public readonly float $length,
        public readonly float $width,
        public readonly float $height,
    ) {
    }
}
