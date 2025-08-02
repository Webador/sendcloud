<?php

namespace JouwWeb\Sendcloud\Exception;

class SendcloudRequestException extends SendcloudClientException
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
        int $code = SendcloudRequestException::CODE_UNKNOWN,
        ?\Throwable $previous = null,
        protected ?int $sendcloudCode = null,
        protected ?string $sendcloudMessage = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the code reported by Sendcloud when available. This usually equals the HTTP status code.
     */
    public function getSendcloudCode(): ?int
    {
        return $this->sendcloudCode;
    }

    /**
     * Returns the error message reported by Sendcloud when available.
     */
    public function getSendcloudMessage(): ?string
    {
        return $this->sendcloudMessage;
    }
}
