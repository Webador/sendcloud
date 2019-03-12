<?php

namespace JouwWeb\SendCloud\Exception;

class SendCloudRequestException extends SendCloudClientException
{
    const CODE_UNKNOWN = 0;
    const CODE_NO_ADDRESS_DATA = 1;
    /** @var int User is not allowed to create a label for a paid package service. */
    const CODE_NOT_ALLOWED_TO_ANNOUNCE = 2;
    const CODES = [
        self::CODE_UNKNOWN,
        self::CODE_NO_ADDRESS_DATA,
        self::CODE_NOT_ALLOWED_TO_ANNOUNCE,
    ];

    /** @var int|null */
    protected $responseCode;

    /** @var string|null */
    protected $responseMessage;

    public function __construct(
        string $message = '',
        int $code = SendCloudRequestException::CODE_UNKNOWN,
        \Throwable $previous = null,
        ?int $responseCode = null,
        ?int $responseMessage = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->responseCode = $responseCode;
        $this->responseMessage = $responseMessage;
    }

    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    public function getResponseMessage(): ?string
    {
        return $this->responseMessage;
    }
}
