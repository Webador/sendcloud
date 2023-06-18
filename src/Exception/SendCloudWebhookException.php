<?php

namespace JouwWeb\SendCloud\Exception;

class SendCloudWebhookException extends \Exception
{
    public const CODE_INVALID_REQUEST = 1;
    public const CODE_VERIFICATION_FAILED = 2;
    public const CODES = [
        self::CODE_INVALID_REQUEST,
        self::CODE_VERIFICATION_FAILED,
    ];
}
