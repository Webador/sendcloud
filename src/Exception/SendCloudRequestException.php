<?php

namespace JouwWeb\SendCloud\Exception;

class SendCloudRequestException extends SendCloudClientException
{
    public const CODE_UNKNOWN = 0;
    public const CODE_NO_ADDRESS_DATA = 1;
    /** User is not allowed to create a label for a paid package service. */
    public const CODE_NOT_ALLOWED_TO_ANNOUNCE = 2;
    public const CODE_UNAUTHORIZED = 3;
    public const CODE_CONNECTION_FAILED = 4;
    public const CODES = [
        self::CODE_UNKNOWN,
        self::CODE_NO_ADDRESS_DATA,
        self::CODE_NOT_ALLOWED_TO_ANNOUNCE,
        self::CODE_UNAUTHORIZED,
        self::CODE_CONNECTION_FAILED,
    ];

    public function __construct(
        string $message = '',
        int $code = SendCloudRequestException::CODE_UNKNOWN,
        \Throwable $previous = null,
        protected ?int $sendCloudCode = null,
        protected ?string $sendCloudMessage = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the code reported by Sendcloud when available. This usually equals the HTTP status code.
     */
    public function getSendCloudCode(): ?int
    {
        return $this->sendCloudCode;
    }

    /**
     * Returns the error message reported by Sendcloud when available.
     */
    public function getSendCloudMessage(): ?string
    {
        return $this->sendCloudMessage;
    }
}
