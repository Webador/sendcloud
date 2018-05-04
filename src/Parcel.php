<?php

namespace Villermen\SendCloud;

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

    // TODO: Add more statuses
    public const STATUS_DELIVERED = 11;
    public const STATUS_NO_LABEL = 999;
    public const STATUS_CANCELLATION_REQUESTED = 1999;
    public const STATUS_CANCELLED = 2000;
    public const STATUSES = [
        self::STATUS_DELIVERED,
        self::STATUS_NO_LABEL,
        self::STATUS_CANCELLATION_REQUESTED,
        self::STATUS_CANCELLED,
    ];

    /** @var \DateTime */
    protected $created;

    /** @var string */
    protected $trackingNumber;

    /** @var string */
    protected $status;

    /** @var int */
    protected $statusId;

    /** @var int */
    protected $id;

    /** @var string[] */
    protected $labelUrls = [];

    /** @var string|null */
    protected $trackingUrl;

    public function __construct(\stdClass $data)
    {
        $this->id = (int)$data->id;
        $this->statusId = (int)$data->status->id;
        $this->status = (string)$data->status->message;
        $this->created = new \DateTime((string)$data->date_created);
        $this->trackingNumber = (string)$data->tracking_number;

        if (isset($data->tracking_url)) {
            $this->trackingUrl = (string)$data->tracking_url;
        } elseif ($this->trackingNumber) {
            // Fall back to using the number if there is no URL (URL is undocumented but convenient)
            $this->trackingUrl = sprintf(
                'https://tracking.sendcloud.sc/?code=%s&verification=%s',
                $this->trackingNumber,
                str_replace(' ', '', (string)$data->postal_code)
            );
        }

        if (isset($data->label->label_printer, $data->label->normal_printer)) {
            $this->labelUrls[self::LABEL_FORMAT_A6] = (string)$data->label->label_printer;
            $this->labelUrls[self::LABEL_FORMAT_A4_TOP_LEFT] = (string)$data->label->normal_printer[0];
            $this->labelUrls[self::LABEL_FORMAT_A4_TOP_RIGHT] = (string)$data->label->normal_printer[1];
            $this->labelUrls[self::LABEL_FORMAT_A4_BOTTOM_LEFT] = (string)$data->label->normal_printer[2];
            $this->labelUrls[self::LABEL_FORMAT_A4_BOTTOM_RIGHT] = (string)$data->label->normal_printer[3];
        }
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusId(): int
    {
        return $this->statusId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabelUrl(int $format): ?string
    {
        return $this->labelUrls[$format] ?? null;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }
}
