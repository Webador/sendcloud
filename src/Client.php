<?php

namespace JouwWeb\SendCloud;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Utils;
use JouwWeb\SendCloud\Exception\SendCloudClientException;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use JouwWeb\SendCloud\Exception\SendCloudStateException;
use JouwWeb\SendCloud\Exception\SendCloudWebhookException;
use JouwWeb\SendCloud\Model\Address;
use JouwWeb\SendCloud\Model\Parcel;
use JouwWeb\SendCloud\Model\ParcelItem;
use JouwWeb\SendCloud\Model\SenderAddress;
use JouwWeb\SendCloud\Model\ShippingMethod;
use JouwWeb\SendCloud\Model\User;
use JouwWeb\SendCloud\Model\WebhookEvent;
use Psr\Http\Message\RequestInterface;

/**
 * Client to perform calls on the Sendcloud API.
 */
class Client
{
    protected const API_BASE_URL = 'https://panel.sendcloud.sc/api/v2/';

    protected \GuzzleHttp\Client $guzzleClient;

    public function __construct(
        protected string $publicKey,
        protected string $secretKey,
        protected ?string $partnerId = null,
        ?string $apiBaseUrl = null
    ) {
        $clientConfig = [
            'base_uri' => $apiBaseUrl ?: self::API_BASE_URL,
            'timeout' => 60, // Mainly because the shipping methods endpoint can take a very long time to respond.
            'auth' => [
                $publicKey,
                $secretKey,
            ],
            'headers' => [
                // Note: We use the deprecated function instead of GuzzleHttp\Utils::defaultUserAgent() to maintain
                // support for Guzzle 6.
                'User-Agent' => 'jouwweb/sendcloud ' . Utils::defaultUserAgent(),
            ],
        ];

        if ($this->partnerId) {
            $clientConfig['headers']['Sendcloud-Partner-Id'] = $this->partnerId;
        }

        $this->guzzleClient = new \GuzzleHttp\Client($clientConfig);
    }

    /**
     * Fetches basic details about the Sendcloud account.
     *
     * @throws SendCloudRequestException
     */
    public function getUser(): User
    {
        try {
            return User::fromData(json_decode((string)$this->guzzleClient->get('user')->getBody(), true)['user']);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'An error occurred while fetching the Sendcloud user.');
        }
    }

    /**
     * Fetches available Sendcloud shipping methods.
     *
     * @param int|null $servicePointId When passed, only methods able to ship to the service point will be returned.
     * @param SenderAddress|int|null $senderAddress The sender address to ship from. Methods available to all of your
     * account's sender addresses will be retrieved when null.
     * @param bool $returnMethodsOnly When true, methods for making a return are returned instead.
     * @return ShippingMethod[]
     * @throws SendCloudClientException
     */
    public function getShippingMethods(
        ?int $servicePointId = null,
        SenderAddress|int|null $senderAddress = null,
        bool $returnMethodsOnly = false
    ): array {
        try {
            $queryData = [];

            if ($servicePointId !== null) {
                $queryData['service_point_id'] = $servicePointId;
            }

            if ($senderAddress !== null) {
                if ($senderAddress instanceof SenderAddress) {
                    $senderAddress = $senderAddress->getId();
                }
                if (!is_int($senderAddress)) {
                    throw new \InvalidArgumentException(
                        '$senderAddress must be an integer or SenderAddress when passed.'
                    );
                }
            } else {
                $senderAddress = 'all';
            }
            $queryData['sender_address'] = $senderAddress;

            if ($returnMethodsOnly) {
                $queryData['is_return'] = 'true';
            }

            $response = $this->guzzleClient->get('shipping_methods', [
                'query' => $queryData,
            ]);
            $shippingMethodsData = json_decode((string)$response->getBody(), true)['shipping_methods'];

            $shippingMethods = array_map(fn (array $shippingMethodData) => (
                ShippingMethod::fromData($shippingMethodData)
            ), $shippingMethodsData);

            // Sort by carrier and name
            usort($shippingMethods, function (ShippingMethod $method1, ShippingMethod $method2) {
                if ($method1->getCarrier() !== $method2->getCarrier()) {
                    return strcasecmp($method1->getCarrier(), $method2->getCarrier());
                }

                return strcasecmp($method1->getName(), $method2->getName());
            });

            return $shippingMethods;
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException(
                $exception,
                'An error occurred while fetching shipping methods from the Sendcloud API.'
            );
        }
    }

    /**
     * Creates a parcel in Sendcloud.
     *
     * @param Address $shippingAddress Address to be shipped to.
     * @param int|null $servicePointId The order will be shipped to the service point if supplied. $shippingAddress is
     * still required as it will be printed on the label.
     * @param string|null $orderNumber
     * @param int|null $weight Weight of the parcel in grams. The default set in Sendcloud will be used if null or zero.
     * @param string|null $customsInvoiceNumber
     * @param int|null $customsShipmentType One of {@see Parcel::CUSTOMS_SHIPMENT_TYPES}.
     * @param ParcelItem[]|null $items Items contained in the parcel.
     * @param string|null $postNumber Number that may be required to send to a service point.
     * @param ?ShippingMethod $shippingMethod
     * @param string|null $errors One of {@see Parcel::ERRORS_VERBOSE}.
     * @return Parcel
     * @throws SendCloudRequestException
     */
    public function createParcel(
        Address $shippingAddress,
        ?int $servicePointId,
        ?string $orderNumber = null,
        ?int $weight = null,
        ?string $customsInvoiceNumber = null,
        ?int $customsShipmentType = null,
        ?array $items = null,
        ?string $postNumber = null,
        ?ShippingMethod $shippingMethod = null,
        ?string $errors = null
    ): Parcel {
        $parcelData = $this->getParcelData(
            null,
            $shippingAddress,
            $servicePointId,
            $orderNumber,
            $weight,
            false,
            $shippingMethod,
            null,
            $customsInvoiceNumber,
            $customsShipmentType,
            $items,
            $postNumber
        );

        try {
            $data = [
                'json' => [
                    'parcel' => $parcelData,
                ],
            ];

            if(isset($errors)){
                $data['query'] = ['errors' => $errors];
            }

            $response = $this->guzzleClient->post('parcels', $data);

            return Parcel::fromData(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not create parcel in Sendcloud.');
        }
    }

    /**
     * Creates a multi-collo parcel in Sendcloud.
     *
     * @param Address $shippingAddress Address to be shipped to.
     * @param int|null $servicePointId The order will be shipped to the service point if supplied. $shippingAddress is
     * still required as it will be printed on the label.
     * @param int|null $weight Weight of the parcel in grams. The default set in Sendcloud will be used if null or zero.
     * @param int|null $customsShipmentType One of {@see Parcel::CUSTOMS_SHIPMENT_TYPES}.
     * @param ParcelItem[]|null $items Items contained in the parcel.
     * @param string|null $postNumber Number that may be required to send to a service point.
     * @param int $quantity Number of parcels to generate for multi-collo shipment.
     * @param string|null $errors One of {@see Parcel::ERRORS_VERBOSE}.
     * @return Parcel[]
     * @throws SendCloudRequestException
     */
    public function createMultiParcel(
        Address $shippingAddress,
        ?int $servicePointId,
        ?string $orderNumber = null,
        ?int $weight = null,
        ?string $customsInvoiceNumber = null,
        ?int $customsShipmentType = null,
        ?array $items = null,
        ?string $postNumber = null,
        ?ShippingMethod $shippingMethod = null,
        ?string $errors = null,
        int $quantity = 1
    ) : array {
        $parcelData = $this->getParcelData(
            null,
            $shippingAddress,
            $servicePointId,
            $orderNumber,
            $weight,
            true,
            $shippingMethod,
            null,
            $customsInvoiceNumber,
            $customsShipmentType,
            $items,
            $postNumber
        );
        $parcelData['quantity'] = $quantity;

        try {
            $parcels = [];

            $data = [
                'json' => [
                    'parcels' => [$parcelData],
                ],
            ];

            if(isset($errors)){
                $data['query'] = ['errors' => $errors];
            }

            $response = $this->guzzleClient->post('parcels', $data);
            $json = json_decode((string)$response->getBody(), true);

            // Retrieve successfully created parcels
            foreach ($json['parcels'] as $parcel) {
                $parcels[] = Parcel::fromData($parcel);
            }

            // Retrieve failed parcels
            /*
            if(isset($json['failed_parcels'])) {
                foreach ($json['failed_parcels'] as $parcel) {
                    $parcels[] = Parcel::fromData($parcel);
                }
            }
            */

            return $parcels;
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not create parcel in Sendcloud.');
        }
    }

    /**
     * Update details of an existing parcel.
     *
     * @throws SendCloudRequestException
     */
    public function updateParcel(Parcel|int $parcel, Address $shippingAddress): Parcel
    {
        $parcelData = $this->getParcelData(
            $this->parseParcelArgument($parcel),
            $shippingAddress,
            null,
            null,
            null,
            false,
            null,
            null,
            null,
            null,
            null,
            null
        );

        try {
            $response = $this->guzzleClient->put('parcels', [
                'json' => [
                    'parcel' => $parcelData,
                ],
            ]);

            return Parcel::fromData(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not update parcel in SendCloud.');
        }
    }

    /**
     * Request a label for an existing parcel.

     * @param SenderAddress|Address|int|null $senderAddress Passing null will pick Sendcloud's default. An Address will
     * use undocumented behavior that will disable branding personalizations.
     * @throws SendCloudRequestException
     */
    public function createLabel(Parcel|int $parcel, ShippingMethod|int $shippingMethod, SenderAddress|Address|int|null $senderAddress): Parcel
    {
        $parcelData = $this->getParcelData(
            $this->parseParcelArgument($parcel),
            null,
            null,
            null,
            null,
            true,
            $shippingMethod,
            $senderAddress,
            null,
            null,
            null,
            null
        );

        try {
            $response = $this->guzzleClient->put('parcels', [
                'json' => [
                    'parcel' => $parcelData,
                ],
            ]);

            return Parcel::fromData(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not create parcel with Sendcloud.');
        }
    }

    /**
     * Cancels or deletes a parcel (depending on status). Returns whether the parcel was successfully cancelled.
     *
     * @throws SendCloudRequestException
     */
    public function cancelParcel(Parcel|int $parcel): bool
    {
        try {
            $this->guzzleClient->post(sprintf('parcels/%s/cancel', $this->parseParcelArgument($parcel)));
            return true;
        } catch (TransferException $exception) {
            $statusCode = ($exception instanceof RequestException && $exception->hasResponse()
                ? $exception->getResponse()->getStatusCode()
                : 0
            );

            // Handle documented rejections
            if (in_array($statusCode, [400, 410])) {
                return false;
            }

            throw $this->parseGuzzleException($exception, 'An error occurred while cancelling the parcel.');
        }
    }

    /**
     * Fetches the PDF label for the given parcel. The parcel must already have a label created.
     *
     * @param int $format `Parcel::LABEL_FORMATS`
     * @return string PDF data.
     * @throws SendCloudClientException
     */
    public function getLabelPdf(Parcel|int $parcel, int $format): string
    {
        if (!in_array($format, Parcel::LABEL_FORMATS)) {
            throw new \InvalidArgumentException('Invalid label format given.');
        }

        if (is_int($parcel)) {
            $parcel = $this->getParcel($parcel);
        } elseif (!($parcel instanceof Parcel)) {
            throw new \InvalidArgumentException('parcel must be an integer or a Parcel.');
        }

        $labelUrl = $parcel->getLabelUrl($format);

        if (!$labelUrl) {
            throw new SendCloudStateException('SendCloud parcel does not have any labels.');
        }

        try {
            return (string)$this->guzzleClient->get($labelUrl)->getBody();
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not retrieve label.');
        }
    }

    /**
     * Fetches a single PDF containing labels for all the given parcels. Parcels that do not have a label available will
     * not be contained in the PDF. If only parcels without a label have been requested it will result in an exception.
     *
     * @param array<Parcel|int> $parcels
     * @param int $format One of `Parcel::LABEL_FORMATS`. The A4 formats will contain up to 4 labels per page.
     * @return string PDF data.
     * @throws SendCloudClientException
     */
    public function getBulkLabelPdf(array $parcels, int $format): string
    {
        $parcelIds = [];
        foreach ($parcels as $parcel) {
            if (is_int($parcel)) {
                $parcelIds[] = $parcel;
            } elseif ($parcel instanceof Parcel) {
                $parcelIds[] = $parcel->getId();
            } else {
                throw new \InvalidArgumentException('Parcels must be Parcel instances or IDs.');
            }
        }

        try {
            $response = $this->guzzleClient->post('labels', [
                'json' => [
                    'label' => [
                        'parcels' => $parcelIds,
                    ]
                ],
            ]);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not retrieve label information.');
        }

        $labelData = json_decode((string)$response->getBody(), true);
        $labelUrl = Utility::getLabelUrlFromData($labelData, $format);
        if (!$labelUrl) {
            throw new SendCloudStateException('No label URL could be obtained from the response.');
        }

        try {
            return (string)$this->guzzleClient->get($labelUrl)->getBody();
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not retrieve label PDF data.');
        }
    }

    /**
     * Fetches the sender addresses configured in SendCloud.
     *
     * @return SenderAddress[]
     * @throws SendCloudRequestException
     */
    public function getSenderAddresses(): array
    {
        try {
            $response = $this->guzzleClient->get('user/addresses/sender');
            $senderAddressesData = json_decode((string)$response->getBody(), true)['sender_addresses'];

            return array_map(function (array $senderAddressData) {
                return SenderAddress::fromData($senderAddressData);
            }, $senderAddressesData);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not retrieve sender addresses.');
        }
    }

    /**
     * Retrieves current parcel data from SendCloud.
     *
     * @throws SendCloudClientException
     */
    public function getParcel(Parcel|int $parcel): Parcel
    {
        try {
            $response = $this->guzzleClient->get('parcels/' . $this->parseParcelArgument($parcel));
            return Parcel::fromData(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not retrieve parcel.');
        }
    }

    /**
     * Parse a webhook event using the client's secret key. See {@see Utility::parseWebhookRequest()} for specifics.
     *
     * @throws SendCloudWebhookException
     */
    public function parseWebhookRequest(RequestInterface $request): WebhookEvent
    {
        return Utility::parseWebhookRequest($request, $this->secretKey);
    }

    /**
     * Returns the return portal URL for the given parcel. Returns `null` when no return portal is configured or the
     * parcel is not associated with a brand.
     */
    public function getReturnPortalUrl(Parcel|int $parcel): ?string
    {
        try {
            $response = $this->guzzleClient->get(sprintf(
                'parcels/%s/return_portal_url',
                $this->parseParcelArgument($parcel)
            ));

            return (string)json_decode($response->getBody(), true)['url'];
        } catch (RequestException $exception) {
            if ($exception->getResponse() && $exception->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * Returns the given arguments as data in SendCloud parcel format.
     *
     * @param ShippingMethod|int|null $shippingMethod Required if requesting a label.
     * @param SenderAddress|Address|int|null $senderAddress Passing null will pick SendCloud's default. An Address will
     * use undocumented behavior that will disable branding personalizations.
     * @param int|null $customsShipmentType One of {@see Parcel::CUSTOMS_SHIPMENT_TYPES}.
     */
    protected function getParcelData(
        ?int $parcelId,
        ?Address $shippingAddress,
        ?string $servicePointId,
        ?string $orderNumber,
        ?int $weight,
        bool $requestLabel,
        ShippingMethod|int|null $shippingMethod,
        SenderAddress|Address|int|null $senderAddress,
        ?string $customsInvoiceNumber,
        ?int $customsShipmentType,
        ?array $items,
        ?string $postNumber
    ): array {
        $parcelData = [];

        if ($parcelId) {
            $parcelData['id'] = $parcelId;
        }

        if ($shippingAddress) {
            $parcelData = array_merge($parcelData, [
                'name' => $shippingAddress->getName(),
                'company_name' => $shippingAddress->getCompanyName() ?? '',
                'address' => $shippingAddress->getAddressLine1(),
                'address_2' => $shippingAddress->getAddressLine2() ?? '',
                'house_number' => $shippingAddress->getHouseNumber() ?? '',
                'city' => $shippingAddress->getCity(),
                'postal_code' => $shippingAddress->getPostalCode(),
                'country' => $shippingAddress->getCountryCode(),
                'email' => $shippingAddress->getEmailAddress(),
                'telephone' => $shippingAddress->getPhoneNumber() ?? '',
                'country_state' => $shippingAddress->getCountryStateCode() ?? '',
            ]);
        }

        if ($servicePointId) {
            $parcelData['to_service_point'] = $servicePointId;
        }

        if ($postNumber) {
            $parcelData['to_post_number'] = $postNumber;
        }

        if ($orderNumber) {
            $parcelData['order_number'] = $orderNumber;
        }

        if ($weight) {
            $parcelData['weight'] = number_format($weight / 1000, 3);
        }

        if ($customsInvoiceNumber) {
            $parcelData['customs_invoice_nr'] = $customsInvoiceNumber;
        }

        if ($customsShipmentType !== null) {
            if (!in_array($customsShipmentType, Parcel::CUSTOMS_SHIPMENT_TYPES)) {
                throw new \InvalidArgumentException(sprintf('Invalid customs shipment type %s.', $customsShipmentType));
            }

            $parcelData['customs_shipment_type'] = $customsShipmentType;
        }

        if ($items) {
            $itemsData = [];

            foreach (array_values($items) as $index => $item) {
                if (!($item instanceof ParcelItem)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Parcel item at index %s is not an instance of ParcelItem.',
                        $index
                    ));
                }

                $itemData = [
                    'description' => $item->getDescription(),
                    'quantity' => $item->getQuantity(),
                    'weight' => number_format($item->getWeight() / 1000, 3),
                    'value' => $item->getValue(),
                ];
                if ($item->getHarmonizedSystemCode()) {
                    $itemData['hs_code'] = $item->getHarmonizedSystemCode();
                }
                if ($item->getOriginCountryCode()) {
                    $itemData['origin_country'] = $item->getOriginCountryCode();
                }
                if ($item->getSku()) {
                    $itemData['sku'] = $item->getSku();
                }
                if ($item->getProductId()) {
                    $itemData['product_id'] = $item->getProductId();
                }
                if ($item->getProperties()) {
                    $itemData['properties'] = $item->getProperties();
                }
                $itemsData[] = $itemData;
            }

            $parcelData['parcel_items'] = $itemsData;
        }

        // Additional fields are only added when requesting a label
        if ($requestLabel) {
            $parcelData['request_label'] = true;

            // Sender address
            if ($senderAddress instanceof SenderAddress) {
                $parcelData['sender_address'] = $senderAddress->getId();
            } elseif ($senderAddress instanceof Address) {
                $parcelData = array_merge($parcelData, [
                    'from_name' => $senderAddress->getName(),
                    'from_company_name' => $senderAddress->getCompanyName() ?? '',
                    'from_address_1' => $senderAddress->getAddressLine1(),
                    'from_address_2' => $senderAddress->getAddressLine2() ?? '',
                    'from_house_number' => $senderAddress->getHouseNumber() ?? '',
                    'from_city' => $senderAddress->getCity(),
                    'from_postal_code' => $senderAddress->getPostalCode(),
                    'from_country' => $senderAddress->getCountryCode(),
                    'from_telephone' => $senderAddress->getPhoneNumber() ?? '',
                    'from_email' => $senderAddress->getEmailAddress(),
                ]);
            } elseif (is_int($senderAddress)) {
                $parcelData['sender_address'] = $senderAddress;
            }

            // Shipping method
            if ($shippingMethod instanceof ShippingMethod) {
                $parcelData['shipment'] = [
                    'id' => $shippingMethod->getId(),
                ];
            } elseif (is_int($shippingMethod)) {
                $parcelData['shipment'] = [
                    'id' => $shippingMethod,
                ];
            } else {
                throw new \InvalidArgumentException(
                    'Shipping method must be passed when requesting a label.'
                );
            }
        }

        return $parcelData;
    }

    protected function parseGuzzleException(
        TransferException $exception,
        string $defaultMessage
    ): SendCloudRequestException {
        $message = $defaultMessage;
        $code = SendCloudRequestException::CODE_UNKNOWN;

        $responseCode = null;
        $responseMessage = null;
        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $responseData = json_decode((string)$exception->getResponse()->getBody(), true);
            $responseCode = $responseData['error']['code'] ?? null;
            $responseMessage = $responseData['error']['message'] ?? null;
        }

        if ($exception instanceof ConnectException) {
            $message = 'Could not contact SendCloud API.';
            $code = SendCloudRequestException::CODE_CONNECTION_FAILED;
        }

        // Precondition failed, parse response message to determine code of exception
        if ($exception->getCode() === 401) {
            $message = 'Invalid public/secret key combination.';
            $code = SendCloudRequestException::CODE_UNAUTHORIZED;
        } elseif ($exception->getCode() === 412) {
            $message = 'SendCloud account is not fully configured yet.';

            if (stripos($responseMessage, 'no address data') !== false) {
                $code = SendCloudRequestException::CODE_NO_ADDRESS_DATA;
            } elseif (stripos($responseMessage, 'not allowed to announce') !== false) {
                $code = SendCloudRequestException::CODE_NOT_ALLOWED_TO_ANNOUNCE;
            }
        }

        return new SendCloudRequestException($message, $code, $exception, $responseCode, $responseMessage);
    }

    protected function parseParcelArgument(Parcel|int $parcel): int
    {
        if (is_int($parcel)) {
            return $parcel;
        }

        if ($parcel instanceof Parcel) {
            return $parcel->getId();
        }

        throw new \InvalidArgumentException('Parcel argument must be a parcel or parcel ID.');
    }
}
