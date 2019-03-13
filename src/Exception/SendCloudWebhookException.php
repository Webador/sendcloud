<?php

namespace JouwWeb\SendCloud\Exception;

class SendCloudWebhookException extends \Exception
{
    const CODE_INVALID_REQUEST = 1;
    const CODE_VERIFICATION_FAILED = 2;
    const CODES = [
        self::CODE_INVALID_REQUEST,
        self::CODE_VERIFICATION_FAILED,
    ];
}
