<?php

namespace JouwWeb\Sendcloud\Model;

class WebhookEvent
{
    public const TYPE_INTEGRATION_CONNECTED = 'integration_connected';
    public const TYPE_INTEGRATION_CREDENTIALS = 'integration_credentials';
    public const TYPE_INTEGRATION_DELETED = 'integration_deleted';
    public const TYPE_INTEGRATION_UPDATED = 'integration_updated';
    public const TYPE_PARCEL_STATUS_CHANGED = 'parcel_status_changed';
    public const TYPE_TEST = 'test_webhook';
    /** The types known to me. The actual type does not necessarily have to match any of these. */
    public const TYPES = [
        self::TYPE_INTEGRATION_CONNECTED,
        self::TYPE_INTEGRATION_CREDENTIALS,
        self::TYPE_INTEGRATION_DELETED,
        self::TYPE_INTEGRATION_UPDATED,
        self::TYPE_PARCEL_STATUS_CHANGED,
        self::TYPE_TEST,
    ];

    public static function fromData(array $data): self
    {
        $created = null;
        if (isset($data['timestamp'])) {
            $timestamp = (int)$data['timestamp'];
            $created = \DateTimeImmutable::createFromFormat('U.u', sprintf(
                '%s.%s',
                floor($timestamp / 1000),
                $timestamp % 1000 * 1000
            ));
        }

        return new self(
            (string)$data['action'],
            array_diff_key($data, array_flip(['action', 'timestamp'])),
            $created,
            isset($data['parcel']) ? Parcel::fromData($data['parcel']) : null,
        );
    }

    public function __construct(
        protected string $type,
        protected array $payload,
        protected ?\DateTimeImmutable $created = null,
        protected ?Parcel $parcel = null,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * The time at which this event was triggered. This can differ from the time the webhook was called.
     */
    public function getCreated(): ?\DateTimeImmutable
    {
        return $this->created;
    }

    public function getParcel(): ?Parcel
    {
        return $this->parcel;
    }

    /**
     * Returns an array with the payload data specified in the event. Use this for properties that aren't parsed like
     * parcel (e.g., integration for integration events).
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
