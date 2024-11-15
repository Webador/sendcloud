<?php

namespace JouwWeb\Sendcloud\Service;

use JouwWeb\Sendcloud\Model\ShippingMethod;

class ShippingMethodService
{
    public static function sortByCarrierAndName(array &$shippingMethods): void
    {
        usort($shippingMethods, function (ShippingMethod $method1, ShippingMethod $method2) {
            if ($method1->getCarrier() !== $method2->getCarrier()) {
                return strcasecmp($method1->getCarrier(), $method2->getCarrier());
            }

            return strcasecmp($method1->getName(), $method2->getName());
        });
    }
}
