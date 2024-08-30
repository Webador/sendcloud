<?php

namespace JouwWeb\Sendcloud;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use JouwWeb\Sendcloud\Exception\SendcloudClientException;
use JouwWeb\Sendcloud\Exception\SendcloudRequestException;
use JouwWeb\Sendcloud\Exception\SendcloudStateException;
use JouwWeb\Sendcloud\Exception\SendcloudWebhookException;
use JouwWeb\Sendcloud\Model\Address;
use JouwWeb\Sendcloud\Model\Parcel;
use JouwWeb\Sendcloud\Model\ParcelItem;
use JouwWeb\Sendcloud\Model\SenderAddress;
use JouwWeb\Sendcloud\Model\ShippingMethod;
use JouwWeb\Sendcloud\Model\User;
use JouwWeb\Sendcloud\Model\WebhookEvent;
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
     * @throws SendcloudRequestException
     */
    public function getUser(): User
    {
        try {
            return User::fromData(json_decode((string)$this->guzzleClient->get('user')->getBody(), true)['user']);
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, 'An error occurred while fetching the Sendcloud user.');
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
     * @throws SendcloudClientException
     * @see https://sendcloud.dev/docs/shipping/shipping-methods/
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
                RequestOptions::QUERY => $queryData,
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
            throw Utility::parseGuzzleException(
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
     * @throws SendcloudRequestException
     * @see https://sendcloud.dev/docs/shipping/create-a-parcel/
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
        ?string $errors = null,
        ?string $shippingMethodCheckoutName = null,
        ?string $totalOrderValue = null,
        ?string $totalOrderValueCurrency = null,
    ): Parcel {
        $parcelData = $this->createParcelData(
            shippingAddress: $shippingAddress,
            servicePointId: $servicePointId,
            orderNumber: $orderNumber,
            weight: $weight,
            shippingMethod: $shippingMethod,
            customsInvoiceNumber: $customsInvoiceNumber,
            customsShipmentType: $customsShipmentType,
            items: $items,
            postNumber: $postNumber,
            shippingMethodCheckoutName: $shippingMethodCheckoutName,
            totalOrderValue: $totalOrderValue,
            totalOrderValueCurrency: $totalOrderValueCurrency,
        );

        try {
            $requestOptions = [
                RequestOptions::JSON => [
                    'parcel' => $parcelData,
                ],
            ];

            if (isset($errors)){
                $requestOptions[RequestOptions::QUERY] = ['errors' => $errors];
            }

            $response = $this->guzzleClient->post('parcels', $requestOptions);

            return Parcel::fromData(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, 'Could not create parcel in Sendcloud.');
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
     * @throws SendcloudRequestException
     * @see https://sendcloud.dev/docs/shipping/multicollo/
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
    ): array {
        $parcelData = $this->createParcelData(
            shippingAddress: $shippingAddress,
            servicePointId: $servicePointId,
            orderNumber: $orderNumber,
            weight: $weight,
            requestLabel: true,
            shippingMethod: $shippingMethod,
            customsInvoiceNumber: $customsInvoiceNumber,
            customsShipmentType: $customsShipmentType,
            items: $items,
            postNumber: $postNumber,
        );
        $parcelData['quantity'] = $quantity;

        try {
            $parcels = [];

            $requestOptions = [
                RequestOptions::JSON => [
                    'parcels' => [$parcelData],
                ],
            ];

            if (isset($errors)){
                $requestOptions[RequestOptions::QUERY] = ['errors' => $errors];
            }

            $response = $this->guzzleClient->post('parcels', $requestOptions);
            $json = json_decode((string)$response->getBody(), true);

            // Retrieve successfully created parcels
            foreach ($json['parcels'] as $parcel) {
                $parcels[] = Parcel::fromData($parcel);
            }

            // Retrieve failed parcels
            /*
            if (isset($json['failed_parcels'])) {
                foreach ($json['failed_parcels'] as $parcel) {
                    $parcels[] = Parcel::fromData($parcel);
                }
            }
            */

            return $parcels;
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, 'Could not create parcel in Sendcloud.');
        }
    }

    /**
     * Update details of an existing parcel.
     *
     * @throws SendcloudRequestException
     */
    public function updateParcel(Parcel|int $parcel, Address $shippingAddress): Parcel
    {
        $parcelData = $this->createParcelData(
            is_int($parcel) ? $parcel : $parcel->getId(),
            shippingAddress: $shippingAddress,
        );

        try {
            $response = $this->guzzleClient->put('parcels', [
                RequestOptions::JSON => [
                    'parcel' => $parcelData,
                ],
            ]);

            return Parcel::fromData(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, 'Could not update parcel in Sendcloud.');
        }
    }

    /**
     * Request a label for an existing parcel.
     * @param SenderAddress|Address|int|null $senderAddress Passing null will pick Sendcloud's default. An Address will
     * use undocumented behavior that will disable branding personalizations.
     * @param bool $applyShippingRules shipping rules can override the given shippingMethod and can be configured in the
     * sendcloud control panel
     * @throws SendcloudRequestException
     */
    public function createLabel(Parcel|int $parcel, ShippingMethod|int $shippingMethod, SenderAddress|Address|int|null $senderAddress, bool $applyShippingRules = false): Parcel
    {
        $parcelData = $this->createParcelData(
            parcelId: is_int($parcel) ? $parcel : $parcel->getId(),
            requestLabel: true,
            shippingMethod: $shippingMethod,
            senderAddress: $senderAddress,
            applyShippingRules: $applyShippingRules
        );

        try {
            $response = $this->guzzleClient->put('parcels', [
                RequestOptions::JSON => [
                    'parcel' => $parcelData,
                ],
            ]);

            return Parcel::fromData(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, 'Could not create parcel with Sendcloud.');
        }
    }

    /**
     * Cancels or deletes a parcel (depending on status). Returns whether the parcel was successfully cancelled.
     *
     * @throws SendcloudRequestException
     */
    public function cancelParcel(Parcel|int $parcel): bool
    {
        try {
            $parcelId = is_int($parcel) ? $parcel : $parcel->getId();
            $this->guzzleClient->post(sprintf('parcels/%s/cancel', $parcelId));
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

            throw Utility::parseGuzzleException($exception, 'An error occurred while cancelling the parcel.');
        }
    }

    /**
     * Fetches the PDF label for the given parcel. The parcel must already have a label created.
     *
     * @param int $format `Parcel::LABEL_FORMATS`
     * @return string PDF data.
     * @throws SendcloudClientException
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
            throw new SendcloudStateException('Sendcloud parcel does not have any labels.');
        }

        try {
            return (string)$this->guzzleClient->get($labelUrl)->getBody();
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, 'Could not retrieve label.');
        }
    }

    /**
     * Fetches a single PDF containing labels for all the given parcels. Parcels that do not have a label available will
     * not be contained in the PDF. If only parcels without a label have been requested it will result in an exception.
     *
     * @param array<Parcel|int> $parcels
     * @param int $format One of `Parcel::LABEL_FORMATS`. The A4 formats will contain up to 4 labels per page.
     * @return string PDF data.
     * @throws SendcloudClientException
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
                RequestOptions::JSON => [
                    'label' => [
                        'parcels' => $parcelIds,
                    ]
                ],
            ]);
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, 'Could not retrieve label information.');
        }

        $labelData = json_decode((string)$response->getBody(), true);
        $labelUrl = Utility::getLabelUrlFromData($labelData, $format);
        if (!$labelUrl) {
            throw new SendcloudStateException('No label URL could be obtained from the response.');
        }

        try {
            return (string)$this->guzzleClient->get($labelUrl)->getBody();
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, 'Could not retrieve label PDF data.');
        }
    }


    /**
     * Fetches a single parcel document based on requested document type, content type, and dpi.
     *
     * If the parcel hasn't had its labels created through {@see self::createLabel()} it will result in an exception.
     *
     * @param string $documentType One of {@see Parcel::DOCUMENT_TYPES}
     * @param string $contentType One of {@see Parcel::DOCUMENT_CONTENT_TYPES}
     * @param int $dpi One of {@see Parcel::DOCUMENT_DPI_VALUES} limited by the selected `$contentType`
     * @return string The contents of the requested document
     * @throws SendcloudClientException
     */
    public function getParcelDocument(Parcel|int $parcel, string $documentType, string $contentType = Parcel::DOCUMENT_CONTENT_TYPE_PDF, int $dpi = Parcel::DOCUMENT_DPI_72): string
    {
        if (!in_array($documentType, Parcel::DOCUMENT_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Document type "%s" is not accepted. Valid types: %s.', $documentType, implode(', ', Parcel::DOCUMENT_TYPES)));
        }

        if (!in_array($contentType, Parcel::DOCUMENT_CONTENT_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Content type "%s" is not accepted. Valid types: %s.', $contentType, implode(', ', Parcel::DOCUMENT_CONTENT_TYPES)));
        }

        if (!in_array($dpi, Parcel::DOCUMENT_DPI_VALUES[$contentType] ?? [], true)) {
            throw new \InvalidArgumentException(sprintf('DPI "%d" is not accepted for "%s". Valid values: %s.', $dpi, $contentType, implode(', ', Parcel::DOCUMENT_DPI_VALUES[$contentType])));
        }

        try {
            $parcelId = is_int($parcel) ? $parcel : $parcel->getId();
            return (string)$this->guzzleClient->get(sprintf('parcels/%s/documents/%s', $parcelId, $documentType), [
                RequestOptions::QUERY => ['dpi' => $dpi],
                RequestOptions::HEADERS => ['Accept' => $contentType],
            ])->getBody();
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, sprintf('Could not retrieve parcel document "%s" for parcel id "%d".', $documentType, $parcelId));
        }
    }

    /**
     * Fetches the sender addresses configured in Sendcloud.
     *
     * @return SenderAddress[]
     * @throws SendcloudRequestException
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
            throw Utility::parseGuzzleException($exception, 'Could not retrieve sender addresses.');
        }
    }

    /**
     * Retrieves current parcel data from Sendcloud.
     *
     * @throws SendcloudClientException
     */
    public function getParcel(Parcel|int $parcel): Parcel
    {
        try {
            $parcelId = is_int($parcel) ? $parcel : $parcel->getId();
            $response = $this->guzzleClient->get(sprintf('parcels/%s', $parcelId));
            return Parcel::fromData(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException($exception, 'Could not retrieve parcel.');
        }
    }

    /**
     * Parse a webhook event using the client's secret key. See {@see Utility::parseWebhookRequest()} for specifics.
     *
     * @throws SendcloudWebhookException
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
            $parcelId = is_int($parcel) ? $parcel : $parcel->getId();
            $response = $this->guzzleClient->get(sprintf(
                'parcels/%s/return_portal_url',
                $parcelId
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
     * Returns the given arguments as data in Sendcloud parcel format.
     *
     * @param ShippingMethod|int|null $shippingMethod Required if requesting a label.
     * @param SenderAddress|Address|int|null $senderAddress Passing null will pick Sendcloud's default. An Address will
     * use undocumented behavior that will disable branding personalizations.
     * @param int|null $customsShipmentType One of {@see Parcel::CUSTOMS_SHIPMENT_TYPES}.
     */
    protected function createParcelData(
        ?int $parcelId = null,
        ?Address $shippingAddress = null,
        ?string $servicePointId = null,
        ?string $orderNumber = null,
        ?int $weight = null,
        bool $requestLabel = false,
        ShippingMethod|int|null $shippingMethod = null,
        SenderAddress|Address|int|null $senderAddress = null,
        ?string $customsInvoiceNumber = null,
        ?int $customsShipmentType = null,
        ?array $items  = null,
        ?string $postNumber = null,
        bool $applyShippingRules = false,
        ?string $shippingMethodCheckoutName = null,
        ?string $totalOrderValue = null,
        ?string $totalOrderValueCurrency = null,
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

        if ($applyShippingRules) {
            $parcelData['apply_shipping_rules'] = true;
        }

        if ($shippingMethodCheckoutName) {
            $parcelData['shipping_method_checkout_name'] = $shippingMethodCheckoutName;
        }

        if ($totalOrderValue && $totalOrderValueCurrency) {
            $parcelData['total_order_value'] = $totalOrderValue;
            $parcelData['total_order_value_currency'] = $totalOrderValueCurrency;
        }

        // Add shipping method regardless of requestLabel flag
        if ($shippingMethod instanceof ShippingMethod) {
            $parcelData['shipment'] = [
                'id' => $shippingMethod->getId(),
            ];
        } elseif (is_int($shippingMethod)) {
            $parcelData['shipment'] = [
                'id' => $shippingMethod,
            ];
        }

        // Additional fields are only added when requesting a label
        if ($requestLabel) {
            $parcelData['request_label'] = true;

            // Sender address
            if ($senderAddress instanceof SenderAddress) {
                $parcelData['sender_address'] = $senderAddress->getId();
            } elseif ($senderAddress instanceof Address) {
                // API will assert that house number is passed separately. See
                // https://github.com/Webador/sendcloud/issues/25.
                if (!$senderAddress->getHouseNumber()) {
                    throw new \InvalidArgumentException(
                        'House number must be passed separately on Address instance passed as sender address.'
                    );
                }

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
        }

        if ($requestLabel && !isset($parcelData['shipment'])) {
            throw new \InvalidArgumentException(
                'Shipping method must be passed when requesting a label.'
            );
        }

        return $parcelData;
    }
}
