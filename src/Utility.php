<?php

namespace JouwWeb\SendCloud;

use JouwWeb\SendCloud\Exception\SendCloudWebhookException;
use Psr\Http\Message\RequestInterface;

class Utility
{
    /**
     * Validates an incoming webhook request using the given secret key. If the request fails to validate an exception
     * will be thrown.
     *
     * @param RequestInterface $request
     * @param string $secretKey
     * @throws SendCloudWebhookException
     */
    public static function verifyWebhookRequest(RequestInterface $request, string $secretKey): void
    {
        $signatureHeader = $request->getHeader('SendCloud-Signature');
        if (count($signatureHeader) === 0) {
            throw new SendCloudWebhookException('Webhook request does not specify a signature header.');
        }
        $signatureHeader = reset($signatureHeader);

        $payload = $request->getBody()->getContents();

        if (hash_hmac('sha256', $payload, $secretKey) !== $signatureHeader) {
            throw new SendCloudWebhookException('Hashed webhook payload does not match SendCloud-supplied header.');
        }
    }
}
