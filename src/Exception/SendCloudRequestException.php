<?php

namespace JouwWeb\SendCloud\Exception;

use GuzzleHttp\Exception\RequestException;

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
        ?RequestException $guzzleException
    ) {
        // Add the error provided by SendCloud to the message
        if ($guzzleException && $guzzleException->hasResponse()) {
            $response = json_decode($guzzleException->getResponse()->getBody());

            if ($response && isset($response->error, $response->error->code, $response->error->message)) {
                $this->responseCode = (int)$response->error->code;
                $this->responseMessage = (string)$response->error->message;

                $message .= sprintf(' (%s: %s)', $this->getResponseCode(), $this->getResponseMessage());
            }
        }

        parent::__construct($message, $code, $guzzleException);
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
