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
use JouwWeb\Sendcloud\Model\ParcelDimensions;
use JouwWeb\Sendcloud\Model\ParcelItem;
use JouwWeb\Sendcloud\Model\SenderAddress;
use JouwWeb\Sendcloud\Model\ShippingMethod;
use JouwWeb\Sendcloud\Model\ShippingProduct;
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
                    $senderAddress = $senderAddress->id;
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

            $shippingMethods = array_map(ShippingMethod::fromData(...), $shippingMethodsData);

            usort($shippingMethods, ShippingMethod::compareByCarrierAndName(...));

            return $shippingMethods;
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException(
                $exception,
                'An error occurred while fetching shipping methods from the Sendcloud API.'
            );
        }
    }

    /**
     * Fetches available Sendcloud shipping products, meaning groups of shipping methods having common characteristics that can be used as filter.
     * A lot of various filters can be used on this Sendcloud route (@see https://sendcloud.dev/docs/shipping/shipping_products/).
     * Only a part of them are in the parameters below.
     *
     * As this function allows to finely filter the shipping methods, it's especially useful if the contract have a huge
     * quantity of shipping methods.
     *
     * Warning, by contrast with getShippingMethods(), here the prices of shipping methods are not given.
     *
     * @param string|null $fromCountry The sender address to ship from. A country ISO 2 code.
     * @param value-of<ShippingProduct::DELIVERY_MODES>|null $deliveryMode The delivery mode that should be used by the
     * returned shipping methods.
     * @param string|null $toCountry The receiver address to ship to. A country ISO 2 code.
     * @param string|null $carrier The carrier of shipping methods that should be filtered on, this carrier must be
     * enabled on the contract to have a result on this filter.
     * @param int|null $weight When not null, only methods with "min_weight" and "max_weight" that encompass the $weight
     * will be returned. Only works with $weightUnit parameter.
     * @param value-of<ShippingProduct::WEIGHT_UNITS>|null $weightUnit Required if parameter $weight is not null.
     * @param bool|null $withReturn When true, methods returned can be used for making a return shipment.
     * @return ShippingProduct[]
     * @throws SendcloudClientException
     * @see https://api.sendcloud.dev/docs/sendcloud-public-api/branches/v2/shipping-products/operations/list-shipping-products
     */
    public function getShippingProducts(
        string $fromCountry,
        ?string $deliveryMode = null,
        ?string $toCountry = null,
        ?string $carrier = null,
        ?int $weight = null,
        ?string $weightUnit = null,
        ?bool $withReturn = null,
    ): array {
        try {
            $queryData = [
                'from_country' => $fromCountry,
            ];

            if ($deliveryMode !== null) {
                if (! in_array($deliveryMode, ShippingProduct::DELIVERY_MODES)) {
                    throw new \InvalidArgumentException(
                        "Delivery mode \"$deliveryMode\" is not available to get shipping products."
                    );
                }
                $queryData['last_mile'] = $deliveryMode;
            }

            if ($toCountry !== null) {
                $queryData['to_country'] = $toCountry;
            }

            if ($carrier !== null) {
                $queryData['carrier'] = $carrier;
            }

            if ($weight !== null) {
                if (! $weightUnit || ! in_array($weightUnit, ShippingProduct::WEIGHT_UNITS)) {
                    throw new \InvalidArgumentException(
                        'Weight unit ' . ($weightUnit ? "\"$weightUnit\" provided is not available" : 'is needed') . ' to get shipping products.'
                    );
                }

                $queryData['weight'] = $weight;
                $queryData['weight_unit'] = $weightUnit;
            }

            if ($withReturn === true) {
                $queryData['returns'] = true;
            }

            $response = $this->guzzleClient->get('shipping-products', [
                RequestOptions::QUERY => $queryData,
            ]);
            $shippingProductsData = json_decode((string)$response->getBody(), true);

            return array_map(ShippingProduct::fromData(...), $shippingProductsData);
        } catch (TransferException $exception) {
            throw Utility::parseGuzzleException(
                $exception,
                'An error occurred while fetching shipping products from the Sendcloud API.'
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
     * @param value-of<Parcel::CUSTOMS_SHIPMENT_TYPES>|null $customsShipmentType
     * @param ParcelItem[]|null $items Items contained in the parcel.
     * @param string|null $postNumber Number that may be required to send to a service point.
     * @param ShippingMethod|null $shippingMethod
     * @param value-of<Parcel::ERRORS_VERBOSE>|null $errors
     * @return Parcel
     * @throws SendcloudRequestException
     * @see https://sendcloud.dev/docs/shipping/create-a-parcel/
     */
    public function createParcel(
        Address $shippingAddress,
        ?int $servicePointId = null,
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
        ?ParcelDimensions $dimensions = null,
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
            dimensions: $dimensions,
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
     * @param value-of<Parcel::CUSTOMS_SHIPMENT_TYPES>|null $customsShipmentType
     * @param ParcelItem[]|null $items Items contained in the parcel.
     * @param string|null $postNumber Number that may be required to send to a service point.
     * @param int $quantity Number of parcels to generate for multi-collo shipment.
     * @param value-of<Parcel::ERRORS_VERBOSE>|null $errors
     * @return Parcel[]
     * @throws SendcloudRequestException
     * @see https://sendcloud.dev/docs/shipping/multicollo/
     */
    public function createMultiParcel(
        Address $shippingAddress,
        ?int $servicePointId = null,
        ?string $orderNumber = null,
        ?int $weight = null,
        ?string $customsInvoiceNumber = null,
        ?int $customsShipmentType = null,
        ?array $items = null,
        ?string $postNumber = null,
        ?ShippingMethod $shippingMethod = null,
        ?string $errors = null,
        int $quantity = 1,
        ?ParcelDimensions $dimensions = null,
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
            dimensions: $dimensions,
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
            is_int($parcel) ? $parcel : $parcel->id,
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
     * @param SenderAddress|Address|int|null $senderAddress Passing null will pick Sendcloud's default. Passing an
     * Address will disable branding personalizations.
     * @param bool $applyShippingRules shipping rules can override the given shippingMethod and can be configured in the
     * sendcloud control panel
     * @param value-of<Parcel::ERRORS_VERBOSE>|null $errors
     * @throws SendcloudRequestException
     */
    public function createLabel(
        Parcel|int $parcel,
        ShippingMethod|int $shippingMethod,
        SenderAddress|Address|int|null $senderAddress,
        bool $applyShippingRules = false,
        ?string $errors = null
    ): Parcel
    {
        $parcelData = $this->createParcelData(
            parcelId: is_int($parcel) ? $parcel : $parcel->id,
            requestLabel: true,
            shippingMethod: $shippingMethod,
            senderAddress: $senderAddress,
            applyShippingRules: $applyShippingRules
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

            $response = $this->guzzleClient->put('parcels', $requestOptions);

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
            $parcelId = is_int($parcel) ? $parcel : $parcel->id;
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
     * @param value-of<Parcel::LABEL_FORMATS> $format
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

        $labelUrl = $parcel->labelUrls[$format] ?? null;

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
     * @param value-of<Parcel::LABEL_FORMATS> $format The A4 formats will contain up to 4 labels per page.
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
                $parcelIds[] = $parcel->id;
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
     * @param value-of<Parcel::DOCUMENT_TYPES> $documentType
     * @param value-of<Parcel::DOCUMENT_CONTENT_TYPES> $contentType
     * @param value-of<Parcel::DOCUMENT_DPI_VALUES> $dpi Limited by the selected `$contentType`
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
            $parcelId = is_int($parcel) ? $parcel : $parcel->id;
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

            return array_map(SenderAddress::fromData(...), $senderAddressesData);
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
            $parcelId = is_int($parcel) ? $parcel : $parcel->id;
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
            $parcelId = is_int($parcel) ? $parcel : $parcel->id;
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
     * @param value-of<Parcel::CUSTOM_SHIPMENT_TYPES>|null $customsShipmentType
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
        ?ParcelDimensions $dimensions = null,
    ): array {
        $parcelData = [];

        if ($parcelId) {
            $parcelData['id'] = $parcelId;
        }

        if ($shippingAddress) {
            $parcelData = array_merge($parcelData, [
                'name' => $shippingAddress->name,
                'company_name' => $shippingAddress->companyName ?? '',
                'address' => $shippingAddress->addressLine1,
                'address_2' => $shippingAddress->addressLine2 ?? '',
                'house_number' => $shippingAddress->houseNumber ?? '',
                'city' => $shippingAddress->city,
                'postal_code' => $shippingAddress->postalCode,
                'country' => $shippingAddress->countryCode,
                'email' => $shippingAddress->emailAddress,
                'telephone' => $shippingAddress->phoneNumber ?? '',
                'country_state' => $shippingAddress->countryStateCode ?? '',
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

        if ($dimensions) {
            // Note that the maximum of 2 decimal places is enforced by the API.
            $parcelData['length'] = number_format($dimensions->length, 2);
            $parcelData['width'] = number_format($dimensions->width, 2);
            $parcelData['height'] = number_format($dimensions->height, 2);
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
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'weight' => number_format($item->weight / 1000, 3),
                    // Sendcloud will error when value contains more than 2 decimal places, yet still wants this to be a
                    // float instead of a string.
                    'value' => round($item->value, 2),
                ];
                if ($item->harmonizedSystemCode) {
                    $itemData['hs_code'] = $item->harmonizedSystemCode;
                }
                if ($item->originCountryCode) {
                    $itemData['origin_country'] = $item->originCountryCode;
                }
                if ($item->sku) {
                    $itemData['sku'] = $item->sku;
                }
                if ($item->productId) {
                    $itemData['product_id'] = $item->productId;
                }
                if ($item->properties) {
                    $itemData['properties'] = $item->properties;
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

        if ($shippingMethod instanceof ShippingMethod) {
            $parcelData['shipment'] = [
                'id' => $shippingMethod->id,
            ];
        } elseif (is_int($shippingMethod)) {
            $parcelData['shipment'] = [
                'id' => $shippingMethod,
            ];
        }

        if ($senderAddress instanceof SenderAddress) {
            $parcelData['sender_address'] = $senderAddress->id;
        } elseif (is_int($senderAddress)) {
            $parcelData['sender_address'] = $senderAddress;
        } elseif ($senderAddress instanceof Address) {
            // API will assert that house number is passed separately. See
            // https://github.com/Webador/sendcloud/issues/25.
            if (!$senderAddress->houseNumber) {
                throw new \InvalidArgumentException(
                    'House number must be passed separately on Address instance passed as sender address.'
                );
            }

            $parcelData = array_merge($parcelData, [
                'from_name' => $senderAddress->name,
                'from_company_name' => $senderAddress->companyName ?? '',
                'from_address_1' => $senderAddress->addressLine1,
                'from_address_2' => $senderAddress->addressLine2 ?? '',
                'from_house_number' => $senderAddress->houseNumber,
                'from_city' => $senderAddress->city,
                'from_postal_code' => $senderAddress->postalCode,
                'from_country' => $senderAddress->countryCode,
                'from_telephone' => $senderAddress->phoneNumber ?? '',
                'from_email' => $senderAddress->emailAddress,
            ]);
        }

        if ($requestLabel) {
            if (!isset($parcelData['shipment'])) {
                throw new \InvalidArgumentException(
                    'Shipping method must be passed when requesting a label.'
                );
            }

            $parcelData['request_label'] = true;
        }

        return $parcelData;
    }
}
