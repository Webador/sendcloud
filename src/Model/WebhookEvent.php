<?php

namespace JouwWeb\SendCloud\Model;

class WebhookEvent
{
    const TYPE_INTEGRATION_CONNECTED = 'integration_connected';
    const TYPE_INTEGRATION_CREDENTIALS = 'integration_credentials';
    const TYPE_INTEGRATION_DELETED = 'integration_deleted';
    const TYPE_INTEGRATION_UPDATED = 'integration_updated';
    const TYPE_PARCEL_STATUS_CHANGED = 'parcel_status_changed';
    const TYPE_TEST = 'test_webhook';
    /** @var string[] The types known to me. The actual type does not necessarily have to match any of these. */
    const TYPES = [
        self::TYPE_INTEGRATION_CONNECTED,
        self::TYPE_INTEGRATION_CREDENTIALS,
        self::TYPE_INTEGRATION_DELETED,
        self::TYPE_INTEGRATION_UPDATED,
        self::TYPE_PARCEL_STATUS_CHANGED,
        self::TYPE_TEST,
    ];

    /** @var string */
    protected $type;

    /** @var \DateTimeImmutable|null */
    protected $created;

    /** @var Parcel|null */
    protected $parcel;

    /** @var mixed[] */
    protected $payload;

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

        if ($this->payload['parcel']) {
            $this->parcel = new Parcel($this->payload['parcel']);
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * The time at which this event was triggered. This can differ from the time the webhook was called.
     *
     * @return \DateTimeImmutable|null
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
     *
     * @return mixed[]
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
