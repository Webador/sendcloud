<?php

namespace JouwWeb\SendCloud;

use JouwWeb\SendCloud\Exception\SendCloudWebhookException;
use JouwWeb\SendCloud\Model\Parcel;
use JouwWeb\SendCloud\Model\WebhookEvent;
use Psr\Http\Message\RequestInterface;

class Utility
{
    /**
     * Verify and parse an incoming webhook request using the specified secret key. A combination of the request's
     * headers and body will be used to verify the and convert the request's payload.
     *
     * If you already have a {@see Client} instance you can use {@see Client::parseWebhookRequest()} to accomplish the
     * same functionality without providing the secret key again.
     *
     * @param RequestInterface $request
     * @param string|null $secretKey Pass a secret key to verify the webhook request or null to disable verification. Do
     * make sure to verify the request with {@see verifyWebhookRequest()} some other time (E.g., after fetching a secret
     * key for the parsed request).
     * @return WebhookEvent
     * @throws SendCloudWebhookException Thrown when the payload fails to validate with the given secret key.
     */
    public static function parseWebhookRequest(RequestInterface $request, ?string $secretKey): WebhookEvent
    {
        if ($secretKey) {
            self::verifyWebhookRequest($request, $secretKey);
        }

        $data = json_decode((string)$request->getBody(), true);

        if (!isset($data['action'])) {
            throw new SendCloudWebhookException(
                'Webhook request does not contain an action and is probably malformed.',
                SendCloudWebhookException::CODE_INVALID_REQUEST
            );
        }

        return new WebhookEvent($data);
    }

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
            throw new SendCloudWebhookException(
                'Webhook request does not specify a signature header.',
                SendCloudWebhookException::CODE_INVALID_REQUEST
            );
        }
        $signatureHeader = reset($signatureHeader);

        if (hash_hmac('sha256', (string)$request->getBody(), $secretKey) !== $signatureHeader) {
            throw new SendCloudWebhookException(
                'Hashed webhook payload does not match Sendcloud-supplied header.',
                SendCloudWebhookException::CODE_VERIFICATION_FAILED
            );
        }
    }

    /**
     * Returns the URL to the label with the given format if it is contained in the data.
     *
     * @param mixed[] $data
     * @param int $format
     * @return string|null
     */
    public static function getLabelUrlFromData(array $data, int $format): ?string
    {
        $labelUrl = null;
        switch ($format) {
            case Parcel::LABEL_FORMAT_A6:
                $labelUrl = ($data['label']['label_printer'] ?? null);
                break;

            case Parcel::LABEL_FORMAT_A4_TOP_LEFT:
            case Parcel::LABEL_FORMAT_A4_TOP_RIGHT:
            case Parcel::LABEL_FORMAT_A4_BOTTOM_LEFT:
            case Parcel::LABEL_FORMAT_A4_BOTTOM_RIGHT:
                $labelUrl = ($data['label']['normal_printer'][$format - 2] ?? null);
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Invalid label format "%s".', $format));
        }

        return ($labelUrl ? (string)$labelUrl : null);
    }
}
