<?php

namespace JouwWeb\SendCloud\Exception;

class SendCloudRequestException extends SendCloudClientException
{
    const CODE_UNKNOWN = 0;
    const CODE_NO_ADDRESS_DATA = 1;
    /** @var int User is not allowed to create a label for a paid package service. */
    const CODE_NOT_ALLOWED_TO_ANNOUNCE = 2;
    const CODE_UNAUTHORIZED = 3;
    const CODE_CONNECTION_FAILED = 4;
    const CODES = [
        self::CODE_UNKNOWN,
        self::CODE_NO_ADDRESS_DATA,
        self::CODE_NOT_ALLOWED_TO_ANNOUNCE,
        self::CODE_UNAUTHORIZED,
        self::CODE_CONNECTION_FAILED,
    ];

    /** @var int|null */
    protected $sendCloudCode;

    /** @var string|null */
    protected $sendCloudMessage;

    public function __construct(
        string $message = '',
        int $code = SendCloudRequestException::CODE_UNKNOWN,
        \Throwable $previous = null,
        ?int $sendCloudCode = null,
        ?string $sendCloudMessage = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->sendCloudCode = $sendCloudCode;
        $this->sendCloudMessage = $sendCloudMessage;
    }

    /**
     * Returns the code reported by SendCloud when available. This usually equals the HTTP status code.
     *
     * @return int|null
     */
    public function getSendCloudCode(): ?int
    {
        return $this->sendCloudCode;
    }

    /**
     * Returns the error message reported by SendCloud when available.
     *
     * @return string|null
     */
    public function getSendCloudMessage(): ?string
    {
        return $this->sendCloudMessage;
    }
}
