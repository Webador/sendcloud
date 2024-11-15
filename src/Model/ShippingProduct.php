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
}
