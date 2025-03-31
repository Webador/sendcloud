<?php

namespace JouwWeb\Sendcloud;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use JouwWeb\Sendcloud\Exception\SendcloudRequestException;
use JouwWeb\Sendcloud\Exception\SendcloudWebhookException;
use JouwWeb\Sendcloud\Model\Parcel;
use JouwWeb\Sendcloud\Model\WebhookEvent;
use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class Utility
{
    /**
     * Verify and parse an incoming webhook request using the specified secret key. A combination of the request's
     * headers and body will be used to verify the and convert the request's payload.
     *
     * If you already have a {@see Client} instance you can use {@see Client::parseWebhookRequest()} to accomplish the
     * same functionality without providing the secret key again.
     *
     * @param string|null $secretKey Pass a secret key to verify the webhook request or null to disable verification. Do
     * make sure to verify the request with {@see verifyWebhookRequest()} some other time (E.g., after fetching a secret
     * key for the parsed request).
     * @throws SendcloudWebhookException Thrown when the payload fails to validate with the given secret key.
     */
    public static function parseWebhookRequest(RequestInterface $request, ?string $secretKey): WebhookEvent
    {
        if ($secretKey) {
            self::verifyWebhookRequest($request, $secretKey);
        }

        $data = json_decode((string)$request->getBody(), true);

        if (!isset($data['action'])) {
            throw new SendcloudWebhookException(
                'Webhook request does not contain an action and is probably malformed.',
                SendcloudWebhookException::CODE_INVALID_REQUEST
            );
        }

        return WebhookEvent::fromData($data);
    }

    /**
     * Validates an incoming webhook request using the given secret key. If the request fails to validate an exception
     * will be thrown.
     *
     * @throws SendcloudWebhookException
     */
    public static function verifyWebhookRequest(RequestInterface $request, string $secretKey): void
    {
        $signatureHeader = $request->getHeader('Sendcloud-Signature');
        if (count($signatureHeader) === 0) {
            throw new SendcloudWebhookException(
                'Webhook request does not specify a signature header.',
                SendcloudWebhookException::CODE_INVALID_REQUEST
            );
        }
        $signatureHeader = reset($signatureHeader);

        if (hash_hmac('sha256', (string)$request->getBody(), $secretKey) !== $signatureHeader) {
            throw new SendcloudWebhookException(
                'Hashed webhook payload does not match Sendcloud-supplied header.',
                SendcloudWebhookException::CODE_VERIFICATION_FAILED
            );
        }
    }

    /**
     * Returns the URL to the label with the given format if it is contained in the data.
     */
    public static function getLabelUrlFromData(array $data, int $format): ?string
    {
        $labelUrl = match ($format) {
            Parcel::LABEL_FORMAT_A6 => ($data['label']['label_printer'] ?? null),
            Parcel::LABEL_FORMAT_A4_TOP_LEFT,
            Parcel::LABEL_FORMAT_A4_TOP_RIGHT,
            Parcel::LABEL_FORMAT_A4_BOTTOM_LEFT,
            Parcel::LABEL_FORMAT_A4_BOTTOM_RIGHT => ($data['label']['normal_printer'][$format - 2] ?? null),
            default => throw new \InvalidArgumentException(sprintf('Invalid label format "%s".', $format)),
        };

        return ($labelUrl ? (string)$labelUrl : null);
    }

    public static function parseGuzzleException(
        TransportExceptionInterface $exception,
        string $defaultMessage
    ): SendcloudRequestException {
        $message = $defaultMessage;
        $code = SendcloudRequestException::CODE_UNKNOWN;

        $responseCode = null;
        $responseMessage = null;
        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $responseData = json_decode((string)$exception->getResponse()->getBody(), true);
            $responseCode = $responseData['error']['code'] ?? null;
            $responseMessage = $responseData['error']['message'] ?? null;
        }

        if ($exception instanceof ConnectException) {
            $message = 'Could not contact Sendcloud API.';
            $code = SendcloudRequestException::CODE_CONNECTION_FAILED;
        }

        // Precondition failed, parse response message to determine code of exception
        if ($exception->getCode() === 401) {
            $message = 'Invalid public/secret key combination.';
            $code = SendcloudRequestException::CODE_UNAUTHORIZED;
        } elseif ($exception->getCode() === 412) {
            $message = 'Sendcloud account is not fully configured yet.';

            if (stripos($responseMessage, 'no address data') !== false) {
                $code = SendcloudRequestException::CODE_NO_ADDRESS_DATA;
            } elseif (stripos($responseMessage, 'not allowed to announce') !== false) {
                $code = SendcloudRequestException::CODE_NOT_ALLOWED_TO_ANNOUNCE;
            }
        }

        return new SendcloudRequestException($message, $code, $exception, $responseCode, $responseMessage);
    }
}
