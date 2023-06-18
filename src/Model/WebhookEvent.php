<?php

namespace JouwWeb\SendCloud\Model;

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

    protected string $type;

    protected \DateTimeImmutable|null $created = null;

    protected ?Parcel $parcel = null;

    protected array $payload;

    public function __construct(array $data)
    {
        $this->type = (string)$data['action'];

        if (isset($data['timestamp'])) {
            $timestamp = (int)$data['timestamp'];
            $this->created = \DateTimeImmutable::createFromFormat('U.u', sprintf(
                '%s.%s',
                floor($timestamp / 1000),
                $timestamp % 1000 * 1000
            ));
        }

        $this->payload = array_diff_key($data, array_flip(['action', 'timestamp']));

        if (isset($this->payload['parcel'])) {
            $this->parcel = new Parcel($this->payload['parcel']);
        }
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
