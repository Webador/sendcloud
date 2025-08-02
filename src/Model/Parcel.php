<?php

namespace JouwWeb\Sendcloud\Model;

use JouwWeb\Sendcloud\Utility;

class Parcel
{
    public const LABEL_FORMAT_A6 = 1;
    public const LABEL_FORMAT_A4_TOP_LEFT = 2;
    public const LABEL_FORMAT_A4_TOP_RIGHT = 3;
    public const LABEL_FORMAT_A4_BOTTOM_LEFT = 4;
    public const LABEL_FORMAT_A4_BOTTOM_RIGHT = 5;
    public const LABEL_FORMATS = [
        self::LABEL_FORMAT_A6,
        self::LABEL_FORMAT_A4_TOP_LEFT,
        self::LABEL_FORMAT_A4_TOP_RIGHT,
        self::LABEL_FORMAT_A4_BOTTOM_LEFT,
        self::LABEL_FORMAT_A4_BOTTOM_RIGHT,
    ];

    // Obtained from https://panel.sendcloud.sc/api/v2/parcels/statuses (with API auth)
    public const STATUS_ANNOUNCED = 1;
    public const STATUS_EN_ROUTE_TO_SORTING_CENTER = 3;
    public const STATUS_DELIVERY_DELAYED = 4;
    public const STATUS_SORTED = 5;
    public const STATUS_NOT_SORTED = 6;
    public const STATUS_BEING_SORTED = 7;
    public const STATUS_DELIVERY_ATTEMPT_FAILED = 8;
    public const STATUS_DELIVERED = 11;
    public const STATUS_AWAITING_CUSTOMER_PICKUP = 12;
    public const STATUS_ANNOUNCED_NOT_COLLECTED = 13;
    public const STATUS_ERROR_COLLECTING = 15;
    public const STATUS_SHIPMENT_PICKED_UP_BY_DRIVER = 22;
    public const STATUS_UNABLE_TO_DELIVER = 80;
    public const STATUS_PARCEL_EN_ROUTE = 91;
    public const STATUS_DRIVER_EN_ROUTE = 92;
    public const STATUS_SHIPMENT_COLLECTED_BY_CUSTOMER = 93;
    public const STATUS_NO_LABEL = 999;
    public const STATUS_READY_TO_SEND = 1000;
    public const STATUS_BEING_ANNOUNCED = 1001;
    public const STATUS_ANNOUNCEMENT_FAILED = 1002;
    public const STATUS_UNKNOWN_STATUS = 1337;
    public const STATUS_CANCELLED_UPSTREAM = 1998;
    public const STATUS_CANCELLATION_REQUESTED = 1999;
    public const STATUS_CANCELLED = 2000;
    public const STATUS_SUBMITTING_CANCELLATION_REQUEST = 2001;
    public const STATUSES = [
        self::STATUS_ANNOUNCED,
        self::STATUS_EN_ROUTE_TO_SORTING_CENTER,
        self::STATUS_DELIVERY_DELAYED,
        self::STATUS_SORTED,
        self::STATUS_NOT_SORTED,
        self::STATUS_BEING_SORTED,
        self::STATUS_DELIVERY_ATTEMPT_FAILED,
        self::STATUS_DELIVERED,
        self::STATUS_AWAITING_CUSTOMER_PICKUP,
        self::STATUS_ANNOUNCED_NOT_COLLECTED,
        self::STATUS_ERROR_COLLECTING,
        self::STATUS_SHIPMENT_PICKED_UP_BY_DRIVER,
        self::STATUS_UNABLE_TO_DELIVER,
        self::STATUS_PARCEL_EN_ROUTE,
        self::STATUS_DRIVER_EN_ROUTE,
        self::STATUS_SHIPMENT_COLLECTED_BY_CUSTOMER,
        self::STATUS_NO_LABEL,
        self::STATUS_READY_TO_SEND,
        self::STATUS_BEING_ANNOUNCED,
        self::STATUS_ANNOUNCEMENT_FAILED,
        self::STATUS_UNKNOWN_STATUS,
        self::STATUS_CANCELLED_UPSTREAM,
        self::STATUS_CANCELLATION_REQUESTED,
        self::STATUS_CANCELLED,
        self::STATUS_SUBMITTING_CANCELLATION_REQUEST,
    ];

    public const CUSTOMS_SHIPMENT_TYPE_GIFT = 0;
    public const CUSTOMS_SHIPMENT_TYPE_DOCUMENTS = 1;
    public const CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_GOODS = 2;
    public const CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_SAMPLE = 3;
    public const CUSTOMS_SHIPMENT_TYPE_RETURNED_GOODS = 4;
    public const CUSTOMS_SHIPMENT_TYPES = [
        self::CUSTOMS_SHIPMENT_TYPE_GIFT,
        self::CUSTOMS_SHIPMENT_TYPE_DOCUMENTS,
        self::CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_GOODS,
        self::CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_SAMPLE,
        self::CUSTOMS_SHIPMENT_TYPE_RETURNED_GOODS,
    ];

    public const DOCUMENT_TYPE_AIR_WAYBILL = 'air-waybill';
    public const DOCUMENT_TYPE_CN23 = 'cn23';
    public const DOCUMENT_TYPE_CN23_DEFAULT = 'cn23-default';
    public const DOCUMENT_TYPE_COMMERCIAL_INVOICE = 'commercial-invoice';
    public const DOCUMENT_TYPE_CP71 = 'cp71';
    public const DOCUMENT_TYPE_LABEL = 'label';
    public const DOCUMENT_TYPE_QR = 'qr';
    public const DOCUMENT_TYPES = [
        self::DOCUMENT_TYPE_AIR_WAYBILL,
        self::DOCUMENT_TYPE_CN23,
        self::DOCUMENT_TYPE_CN23_DEFAULT,
        self::DOCUMENT_TYPE_COMMERCIAL_INVOICE,
        self::DOCUMENT_TYPE_CP71,
        self::DOCUMENT_TYPE_LABEL,
        self::DOCUMENT_TYPE_QR,
    ];

    public const DOCUMENT_CONTENT_TYPE_PDF = 'application/pdf';
    public const DOCUMENT_CONTENT_TYPE_ZPL = 'application/zpl';
    public const DOCUMENT_CONTENT_TYPE_PNG = 'image/png';
    public const DOCUMENT_CONTENT_TYPES = [
        self::DOCUMENT_CONTENT_TYPE_PDF,
        self::DOCUMENT_CONTENT_TYPE_ZPL,
        self::DOCUMENT_CONTENT_TYPE_PNG,
    ];

    public const DOCUMENT_DPI_72 = 72;
    public const DOCUMENT_DPI_150 = 150;
    public const DOCUMENT_DPI_203 = 203;
    public const DOCUMENT_DPI_300 = 300;
    public const DOCUMENT_DPI_600 = 600;
    public const DOCUMENT_DPI_VALUES = [
        self::DOCUMENT_CONTENT_TYPE_PDF => [self::DOCUMENT_DPI_72],
        self::DOCUMENT_CONTENT_TYPE_ZPL => [self::DOCUMENT_DPI_203, self::DOCUMENT_DPI_300, self::DOCUMENT_DPI_600],
        self::DOCUMENT_CONTENT_TYPE_PNG => [self::DOCUMENT_DPI_150, self::DOCUMENT_DPI_300],
    ];

    /**
     * Constants for the 'errors' query parameter for parcel creation
     * @see https://api.sendcloud.dev/docs/sendcloud-public-api/parcels-and-error-handling#errorsverbose
     */
    public const ERROR_VERBOSE = 'verbose';
    public const ERROR_VERBOSE_CARRIER = 'verbose-carrier';
    public const ERRORS_VERBOSE = [
        self::ERROR_VERBOSE,
        self::ERROR_VERBOSE_CARRIER
    ];

    public static function fromData(array $data): self
    {
        $labelUrls = [];
        foreach (self::LABEL_FORMATS as $format) {
            $labelUrl = Utility::getLabelUrlFromData($data, $format);
            if ($labelUrl) {
                $labelUrls[$format] = $labelUrl;
            }
        }

        $items = [];
        if (isset($data['parcel_items'])) {
            foreach ((array)$data['parcel_items'] as $itemData) {
                $items[] = ParcelItem::fromData($itemData);
            }
        }

        $errors = [];
        if (isset($data['errors'])) {
            foreach ((array)$data['errors'] as $key => $itemData) {
                $errors[$key][] = $itemData;
            }
        }

        return new self(
            id: (int)$data['id'],
            statusId: (int)$data['status']['id'],
            statusMessage: (string)$data['status']['message'],
            created: new \DateTimeImmutable((string)$data['date_created']),
            trackingNumber: (string)$data['tracking_number'],
            weight: (int)round(((float)$data['weight']) * 1000),
            address: Address::fromParcelData($data),
            labelUrls: count($labelUrls) > 0 ? $labelUrls : null,
            trackingUrl: isset($data['tracking_url']) ?  (string)$data['tracking_url'] : null,
            carrier: isset($data['carrier']['code']) ? (string)$data['carrier']['code'] : null,
            orderNumber: isset($data['order_number']) ? (string)$data['order_number']: null,
            shippingMethodId: isset($data['shipment']['id']) ? (int)$data['shipment']['id'] : null,
            servicePointId: isset($data['to_service_point']) ? (int)$data['to_service_point'] : null,
            customsInvoiceNumber: isset($data['customs_invoice_nr']) ? (string)$data['customs_invoice_nr'] : null,
            customsShipmentType: isset($data['customs_shipment_type']) ? (int)$data['customs_shipment_type'] : null,
            items: $items,
            errors: $errors,
            dimensions: isset($data['length'], $data['width'], $data['height']) ? new ParcelDimensions(
                length: (float)$data['length'],
                width: (float)$data['width'],
                height: (float)$data['height'],
            ) : null,
        );
    }

    /**
     * @param array<value-of<self::FORMATS>, string>|null $labelUrls
     * @param ParcelItem[] $items
     * @param array<string, string> $errors
     */
    public function __construct(
        public readonly int $id,
        public readonly int $statusId,
        public readonly string $statusMessage,
        public readonly \DateTimeImmutable $created,
        public readonly string $trackingNumber,
        public readonly int $weight,
        public readonly Address $address,
        public readonly ?array $labelUrls = null,
        public readonly ?string $trackingUrl = null,
        public readonly ?string $carrier = null,
        public readonly ?string $orderNumber = null,
        public readonly ?int $shippingMethodId = null,
        public readonly ?int $servicePointId = null,
        public readonly ?string $customsInvoiceNumber = null,
        public readonly ?int $customsShipmentType = null,
        public readonly array $items = [],
        public readonly array $errors = [],
        public readonly ?ParcelDimensions $dimensions = null,
    ) {
    }

    /**
     * @deprecated Use property.
     */
    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @deprecated Use property.
     */
    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    /**
     * @deprecated Use property.
     */
    public function getStatusMessage(): string
    {
        return $this->statusMessage;
    }

    /**
     * @deprecated Use property.
     */
    public function getStatusId(): int
    {
        return $this->statusId;
    }

    /**
     * @deprecated Use property.
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function hasLabel(): bool
    {
        return (bool)$this->labelUrls;
    }

    public function getLabelUrl(int $format): ?string
    {
        return $this->labelUrls[$format] ?? null;
    }

    /**
     * @deprecated Use property.
     */
    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    /**
     * @deprecated Use property.
     */
    public function getAddress(): Address
    {
        return $this->address;
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
    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    /**
     * @deprecated Use property.
     */
    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    /**
     * @deprecated Use property.
     */
    public function getShippingMethodId(): ?int
    {
        return $this->shippingMethodId;
    }

    /**
     * @deprecated Use property.
     */
    public function getServicePointId(): ?int
    {
        return $this->servicePointId;
    }

    /**
     * @deprecated Use property.
     */
    public function getCustomsInvoiceNumber(): ?string
    {
        return $this->customsInvoiceNumber;
    }

    /**
     * @deprecated Use property.
     */
    public function getCustomsShipmentType(): ?int
    {
        return $this->customsShipmentType;
    }

    /**
     * @return ParcelItem[]
     * @deprecated Use property.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return array<string, string>
     * @deprecated Use property.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'address' => $this->address->toArray(),
            'carrier' => $this->carrier,
            'created' => $this->created->format(\DateTimeInterface::ATOM),
            'id' => $this->id,
            'labels' => array_map(fn (int $format): ?string => (
                $this->labelUrls[$format] ?? null
            ), self::LABEL_FORMATS),
            'orderNumber' => $this->orderNumber,
            'servicePointId' => $this->servicePointId,
            'shippingMethodId' => $this->shippingMethodId,
            'statusId' => $this->statusId,
            'statusMessage' => $this->statusMessage,
            'trackingNumber' => $this->trackingNumber,
            'trackingUrl' => $this->trackingUrl,
            'weight' => $this->weight,
            'customsInvoiceNumber' => $this->customsInvoiceNumber,
            'customsShipmentType' => $this->customsShipmentType,
            'items' => array_map(fn (ParcelItem $item): array => (
                $item->toArray()
            ), $this->items),
        ];
    }

    public function __toString(): string
    {
        if ($this->orderNumber) {
            $suffix = sprintf('for order %s', $this->orderNumber);
        } else {
            $suffix = sprintf('for %s', $this->address);
        }

        return sprintf('parcel %s %s', $this->id, $suffix);
    }
}
