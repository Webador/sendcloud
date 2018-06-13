<?php

namespace JouwWeb\SendCloud\Exception;

class SendCloudConfigurationException extends SendCloudRequestException
{
    const CODE_UNKNOWN = 0;
    const CODE_NO_ADDRESS_DATA = 1;
    /** @var int User is not allowed to create a label for a paid package service. */
    const CODE_NOT_ALLOWED_TO_ANNOUNCE = 2;

    const TYPES = [
        self::CODE_UNKNOWN,
        self::CODE_NO_ADDRESS_DATA,
        self::CODE_NOT_ALLOWED_TO_ANNOUNCE,
    ];
}
